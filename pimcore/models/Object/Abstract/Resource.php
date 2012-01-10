<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Abstract_Resource extends Element_Resource {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumnsBase = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumnsBase = $this->getValidTableColumns("objects");
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM objects WHERE o_id = ?", $id);
        if ($data["o_id"]) {
            $this->assignVariablesToModel($data);
        }
        else {
            throw new Exception("Object with the ID " . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the object from database for the given path
     *
     * @param string $path
     * @return void
     */
    public function getByPath($path) {

        // check for root node
        $_path = $path != "/" ? $_path = dirname($path) : $path;
        $_path = str_replace("\\", "/", $_path); // windows patch
        $_key = basename($path);
        $_path .= $_path != "/" ? "/" : "";

        $data = $this->db->fetchRow("SELECT o_id FROM objects WHERE o_path = " . $this->db->quote($_path) . " and `o_key` = " . $this->db->quote($_key));

        if ($data["o_id"]) {
            $this->assignVariablesToModel($data);
        }
        else {
            throw new Exception("object doesn't exist");
        }
    }


    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {


        $this->db->insert("objects", array());
        $this->model->setO_id($this->db->lastInsertId());

        if (!$this->model->geto_key()) {
            $this->model->setO_key($this->db->lastInsertId());
        }
        $this->model->save();


    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {

        $object = get_object_vars($this->model);

        $data = array();
        foreach ($object as $key => $value) {
            if (in_array($key, $this->validColumnsBase)) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        // first try to insert a new record, this is because of the recyclebin restore
        try {
            $this->db->insert("objects", $data);
        }
        catch (Exception $e) {
            $this->db->update("objects", $data, $this->db->quoteInto("o_id = ?", $this->model->getO_id() ));
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("objects", $this->db->quoteInto("o_id = ?", $this->model->getO_id() ));
    }

    /**
     * Updates the paths for children, children's properties and children's permissions in the database
     *
     * @param string $oldPath
     * @return void
     */
    public function updateChildsPaths($oldPath) {
        //get objects to empty their cache
        $objects = $this->db->fetchAll("SELECT o_id,o_path FROM objects WHERE o_path LIKE ?", $oldPath . "%");

        //update object child paths
        $this->db->query("update objects set o_path = replace(o_path," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where o_path like " . $this->db->quote($oldPath . "/%") .";");

        //update object child permission paths
        $this->db->query("update objects_permissions set cpath = replace(cpath," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");

        //update object child properties paths
        $this->db->query("update properties set cpath = replace(cpath," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");


        foreach ($objects as $object) {
            // empty object cache
            try {
                Pimcore_Model_Cache::clearTag("object_" . $object["o_id"]);
            }
            catch (Exception $e) {
            }
        }

    }


    /**
     * deletes all properties for the object from database
     *
     * @return void
     */
    public function deleteAllProperties() {
        $this->db->delete("properties", $this->db->quoteInto("cid = ? AND ctype = 'object'", $this->model->getId()));
    }

    /**
     * @return string retrieves the current full object path from DB
     */
    public function getCurrentFullPath() {
        try {
            $data = $this->db->fetchRow("SELECT CONCAT(o_path,`o_key`) as o_path FROM objects WHERE o_id = ?", $this->model->getId());
        }
        catch (Exception $e) {
            Logger::error("could not get current object path from DB");
        }
        return $data['o_path'];
    }


    /**
     * Get the properties for the object from database and assign it
     *
     * @return void
     */
    public function getProperties($onlyInherited = false) {

        $properties = array();

        // collect properties via parent - ids
        $parentIds = array(1);
        $obj = $this->model->getParent();

        if($obj) {
            while($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        
        $propertiesRaw = $this->db->fetchAll("SELECT * FROM properties WHERE ((cid IN (".implode(",",$parentIds).") AND inheritable = 1) OR cid = ? )  AND ctype='object'", $this->model->getId());

        // because this should be faster than mysql
        usort($propertiesRaw, function($left,$right) {
           return strcmp($left["cpath"],$right["cpath"]);
        });

        foreach ($propertiesRaw as $propertyRaw) {

            try {
                $property = new Property();
                $property->setType($propertyRaw["type"]);
                $property->setCid($this->model->getO_Id());
                $property->setName($propertyRaw["name"]);
                $property->setCtype("object");
                $property->setDataFromResource($propertyRaw["data"]);
                $property->setInherited(true);
                if ($propertyRaw["cid"] == $this->model->getO_Id()) {
                    $property->setInherited(false);
                }
                $property->setInheritable(false);
                if ($propertyRaw["inheritable"]) {
                    $property->setInheritable(true);
                }
                
                if($onlyInherited && !$property->getInherited()) {
                    continue;
                }
                
                $properties[$propertyRaw["name"]] = $property;
            }
            catch (Exception $e) {
                Logger::error("can't add property " . $propertyRaw["name"] . " to object " . $this->model->getFullPath());
            }
        }
        
        // if only inherited then only return it and dont call the setter in the model
        if($onlyInherited) {
            return $properties;
        }
        
        $this->model->setO_Properties($properties);

        return $properties;
    }


    /**
     * @return array
     *
     */
    public function getPermissions() {

        $permissions = array();

        $permissionsRaw = $this->db->fetchAll("SELECT id FROM objects_permissions WHERE cid = ? ORDER BY cpath ASC", $this->model->geto_Id());

        $userIdMappings = array();
        foreach ($permissionsRaw as $permissionRaw) {
            $permissions[] = Object_Permissions::getById($permissionRaw["id"]);
        }


        $this->model->setO_Permissions($permissions);

        return $permissions;
    }


    /**
     *
     * @return void
     */
    public function deleteAllPermissions() {
        $this->db->delete("objects_permissions", $this->db->quoteInto("cid = ?", $this->model->getO_Id()));
    }

    /**
     * get recursively the permissions for the passed user under consideration of the parent user group
     *
     * @param User $user
     * @return Object_Permissions
     */
    public function getPermissionsForUser(User $user) {

        $pathParts = explode("/", $this->model->getO_Path() . $this->model->getO_Key());
        unset($pathParts[0]);
        $tmpPathes = array();
        $pathConditionParts[] = "cpath = '/'";
        foreach ($pathParts as $pathPart) {
            $tmpPathes[] = $pathPart;
            $pathConditionParts[] = $this->db->quoteInto("cpath = ?", "/" . implode("/", $tmpPathes));
        }

        $pathCondition = implode(" OR ", $pathConditionParts);

        $permissionRaw = $this->db->fetchRow("SELECT id FROM objects_permissions WHERE (" . $pathCondition . ") AND userId = ? ORDER BY cpath DESC LIMIT 1",  $user->getId());

        //path condition for parent object
        $parentObjectPathParts = array_slice($pathParts, 0, -1);
        $parentObjectPathConditionParts[] = "cpath = '/'";
        foreach ($parentObjectPathParts as $parentObjectPathPart) {
            $parentObjectTmpPaths[] = $parentObjectPathPart;
            $parentObjectPathConditionParts[] = $this->db->quoteInto("cpath = ?", "/" . implode("/", $parentObjectTmpPaths));
        }
        $parentObjectPathCondition = implode(" OR ", $parentObjectPathConditionParts);
        $parentObjectPermissionRaw = $this->db->fetchRow("SELECT id FROM objects_permissions WHERE (" . $parentObjectPathCondition . ") AND userId = ? ORDER BY cpath DESC LIMIT 1", $user->getId());
        $parentObjectPermissions = new Object_Permissions();
        if ($parentObjectPermissionRaw["id"]) {
            $parentObjectPermissions = Object_Permissions::getById($parentObjectPermissionRaw["id"]);
        }


        $parentUser = $user->getParent();
        if ($parentUser instanceof User and $parentUser->isAllowed("objects")) {
            $parentPermission = $this->getPermissionsForUser($parentUser);
        } else $parentPermission = null;

        $permission = new Object_Permissions();

        if ($permissionRaw["id"] and $parentPermission instanceof Object_Permissions) {

            //consider user group permissions
            $permission = Object_Permissions::getById($permissionRaw["id"]);
            $permissionKeys = $permission->getValidPermissionKeys();

            foreach ($permissionKeys as $key) {
                $getter = "get" . ucfirst($key);
                $setter = "set" . ucfirst($key);

                if ((!$permission->getList() and !$parentPermission->getList())  or !$parentObjectPermissions->getList()) {
                    //no list - return false for all
                    $permission->$setter(false);
                } else if ($parentPermission->$getter()) {
                    //if user group allows -> return true, it overrides the user permission!
                    $permission->$setter(true);
                }
            }


        } else if ($permissionRaw["id"]) {
            //use user permissions, no user group to override anything
            $permission = Object_Permissions::getById($permissionRaw["id"]);
            //check parent object's list permission and current list permission
            if (!$parentObjectPermissions->getList() or !$permission->getList()) {
                $permissionKeys = $permission->getValidPermissionKeys();
                foreach ($permissionKeys as $key) {
                    $setter = "set" . ucfirst($key);
                    $permission->$setter(false);
                }
            }

        } else if ($parentPermission instanceof Object_Permissions and $parentPermission->getId() > 0) {
            //use user group permissions - no permission found for user at all
            $permission = $parentPermission;
            if (!$parentObjectPermissions->getList() or !$permission->getList()) {
                $permissionKeys = $permission->getValidPermissionKeys();
                foreach ($permissionKeys as $key) {
                    $setter = "set" . ucfirst($key);
                    $permission->$setter(false);
                } 
            }

        } else {
            //neither user group nor user has permissions set -> use default all allowed
            $permission->setUser($user);
            $permission->setUserId($user->getId());
            $permission->setUsername($user->getUsername());
            $permission->setCid($this->model->getId());
            $permission->setCpath($this->model->getFullPath());

        }

        $this->model->setO_UserPermissions($permission);
        return $permission;
    }

    /**
     * Quick test if there are childs
     *
     * @return boolean
     */
    public function hasChilds($objectTypes = array(Object_Abstract::OBJECT_TYPE_OBJECT, Object_Abstract::OBJECT_TYPE_FOLDER)) {
        $c = $this->db->fetchRow("SELECT o_id FROM objects WHERE o_parentId = ? AND o_type IN ('" . implode("','", $objectTypes) . "')", $this->model->getO_id());

        $state = false;
        if ($c["o_id"]) {
            $state = true;
        }

        $this->model->o_hasChilds = $state;

        return $state;
    }

    /**
     * returns the amount of directly childs (not recursivly)
     *
     * @return integer
     */
    public function getChildAmount($objectTypes = array(Object_Abstract::OBJECT_TYPE_OBJECT, Object_Abstract::OBJECT_TYPE_FOLDER)) {
        $c = $this->db->fetchRow("SELECT COUNT(*) AS count FROM objects WHERE o_parentId = ? AND o_type IN ('" . implode("','", $objectTypes) . "')", $this->model->getO_id());
        return $c["count"];
    }


    public function getTypeById($id) {

        $t = $this->db->fetchRow("SELECT o_type,o_className,o_classId FROM objects WHERE o_id = ?", $id);
        return $t;
    }
    
    
    public function isLocked () {
        
        // check for an locked element below this element
        $belowLocks = $this->db->fetchRow("SELECT o_id FROM objects WHERE o_path LIKE ? AND o_locked IS NOT NULL AND o_locked != '';", $this->model->getFullpath()."%");
        
        if(is_array($belowLocks) && count($belowLocks) > 0) {
            return true;
        }
        
        // check for an inherited lock
        $pathParts = explode("/", $this->model->getFullPath());
        unset($pathParts[0]);
        $tmpPathes = array();
        $pathConditionParts[] = "CONCAT(o_path,`o_key`) = '/'";
        foreach ($pathParts as $pathPart) {
            $tmpPathes[] = $pathPart;
            $pathConditionParts[] = $this->db->quoteInto("CONCAT(o_path,`o_key`) = ?", "/" . implode("/", $tmpPathes));
        }

        $pathCondition = implode(" OR ", $pathConditionParts);
        $inhertitedLocks = $this->db->fetchAll("SELECT o_id FROM objects WHERE (" . $pathCondition . ") AND o_locked = 'propagate';");
        
        if(is_array($inhertitedLocks) && count($inhertitedLocks) > 0) {
            return true;
        }
        
        
        return false;
    }

    public function getClasses() {

        $classIds = $this->db->fetchAll("SELECT o_classId FROM objects WHERE o_path LIKE ? AND o_type = 'object' GROUP BY o_classId", $this->model->getFullPath() . "%");

        $classes = array();
        foreach ($classIds as $classId) {
            $classes[] = Object_Class::getById($classId["o_classId"]);
        }

        return $classes;
    }    

}
