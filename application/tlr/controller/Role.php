<?php

namespace app\tlr\controller;
use app\tlr\model\UserModel;
use app\tlr\model\RoleModel;
use app\tlr\model\UserRoleModel;
use app\tlr\model\PermissionModel;
use app\tlr\model\RolePermissionModel;
use app\tlr\model\ControllerModel;
use app\tlr\model\LogModel;
use think\Controller;
use think\Request;
use gmars\rbac\Rbac;

class Role extends Controller
{
	// 角色管理
	public function index(Request $request)
	{
		$rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限");
            exit();
        }
		$Role = new RoleModel;
		$roles = $Role->select();
		$data = ["roles" => $roles];
        $this->assign($data);
        return $this->fetch('index');
	}

	//添加角色
	public function addRole(Request $request)
	{
		$rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限");
            exit();
        }
		$rbacObj = new Rbac();
		$rbacObj->createRole($_POST);
		$Role = new RoleModel();
        $Log = new LogModel();
        $Log->save(["uid" => session('uid'), "action" => $Role->getlastsql(), "time" => date("Y-m-d H:i:s")]);
		$this->success("操作成功");
	}

	// 角色权限列表
	public function setPermission(Request $request)
	{
		$rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限");
            exit();
        }
		$Permission = new PermissionModel;
		// 所有权限
		$permissions = $Permission->select();
		$RolePermission = new RolePermissionModel;
		// 该角色所拥有的权限
		$rolePermissions = $RolePermission->where('role_id', $_GET['id'])->select();
		for($i = 0; $i < sizeof($permissions); $i++) {
			$permissions[$i]['have'] = 0;
			for($j = 0; $j < sizeof($rolePermissions); $j++) {
				if($permissions[$i]['id'] == $rolePermissions[$j]['permission_id']) {
					$permissions[$i]['have'] = 1;
					break;
				}
			}
		}
		$Controller = new ControllerModel;
		$controllers = $Controller->select();
		foreach ($controllers as $key => $value) {
			$tmp =array();
			foreach ($permissions as $k => $v) {
				if($v['cid'] == $value['cid'])
					array_push($tmp, $v);
			}
			$controllers[$key]['permissions'] = $tmp;
		}

		$data = ["controllers" => $controllers, "role" => $_GET];
        $this->assign($data);
        return $this->fetch('setPermission');
	}

	// 为角色分配权限
	public function setPermissionHandle(Request $request)
	{
		$rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限");
            exit();
        }
		$RolePermission = new RolePermissionModel;
		$rolePermissions = $RolePermission->where('role_id', $_POST['role_id'])->delete();
        if(isset($_POST['permissions'])){
        	$rbacObj = new Rbac();
			$rbacObj->assignRolePermission($_POST['role_id'], $_POST['permissions']);
			$RolePermission = new RolePermissionModel();
	        $Log = new LogModel();
	        $Log->save(["uid" => session('uid'), "action" => $RolePermission->getlastsql(), "time" => date("Y-m-d H:i:s")]);
        }
		$this->success("操作成功");
	}
}