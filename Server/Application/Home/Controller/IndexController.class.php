<?php
namespace Home\Controller;

use Think\Controller;
use Think\Exception;

class IndexController extends Controller
{

    // 自动运行方法,判断是否登录
    public function _initialize() {
        //当前为登录页时不执行该操作
        if (ACTION_NAME != "login") {
            //判断session['adminaccount']是否为空，是的话跳转到登陆界面
            if (!isset($_SESSION['adminaccount'])) {
                echo "<script>alert('用户未登录或登陆超时');</script>";
                $this->redirect("/Home/Index/login");
            } else {
                //显示登录的管理员帐号
                $adminaccount = $_SESSION['adminaccount'];
                $admin = M('admin')->where("adminaccount='" . $adminaccount . "'")->select();
                $name = $admin[0]['adminname'];
                $this->assign("name", $name);
            }
        }
    }

    // 主页
    public function index()
    {
        $vo = M('user')->order('id')->select();
        $this->assign("list", $vo);
        $this->display();
    }

    //登录页
    public function login() {
        //不加载模板页
        C('LAYOUT_ON', FALSE);
        $this->display();
        if (IS_POST) {
            $admin = M('admin');
            $adminaccount = $_POST['adminaccount'];
            $password = $_POST['password'];
            //这里使用md5加密
            $password = md5($password);
            if ($adminaccount == "" || $password == "") {
                echo "<script>alert('请输入用户名和密码！');history.go(-1);</script>";
            } else {
                $result = $admin->where('adminaccount="%s" and adminpassword="%s"', $adminaccount, $password)->select();
                if ($result) {
                    //将用户账号存入session
                    $_SESSION['adminaccount'] = $adminaccount;
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('登陆成功');</script>";
                    $this->redirect("/Home/Index");
                } else {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('登录失败');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
                }
            }
        }
    }

    // 房间列表
    public function rooms() {
        $room = M('room');
        $vo = $room->select();
        $this->assign('list', $vo);
        $this->display();
    }

    // 添加或修改房间
    public function room() {
        $id = I('request.id');
        if($id) {  // 修改操作
            $room = M('room');
            $vo = $room->where("id=" . $id)->select();
            $this->assign("list", $vo);
            $this->display();
            if(IS_POST) {
                if(isset($_POST['save'])) {
                    $room = M("room");
                    $room->id = $id;
                    $room->name = $_POST['name'];
                    $room->capacity = $_POST['capacity'];
                    $result = $room->save();
                    if($result) {
                        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                        echo "<script>alert('修改成功');</script>";
                        $this->redirect("/Home/Index/rooms");
                    } else {
                        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                        echo '<script type="text/javascript">alert("修改失败")</script>';
                    }
                }
            }
        } else {    // 新增操作
            $this->display();
            if (IS_POST) {
                if (isset($_POST['save'])) {
                    $room = M("room");
                    $room->name = $_POST['name'];
                    $room->capacity = $_POST['capacity'];
                    $room->nownum = 0;
                    // 查询房间名是否存在
                    $temp_room = M('room');
                    $repeat_room = $temp_room->where("name='%s'", $room->name)->find();
                    if($repeat_room) {
                        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                        echo '<script type="text/javascript">alert("房间名已存在")</script>';
                    } else {
                        $result = $room->add();
                        if ($result) {
                            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                            echo '<script type="text/javascript">alert("新增成功")</script>';
                            $this->redirect("/Home/Index/rooms");
                        } else {
                            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                            echo '<script type="text/javascript">alert("新增失败")</script>';
                        }
                    }
                }
            }
        }
    }

    //删除房间
    public function deleteroom() {
        $id = I('request.id');
        $room = M('room');
        $result = $room->delete($id);
        if ($result) {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo "<script>alert('删除成功');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
        } else {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo "<script>alert('删除失败');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
        }
    }


    // 宠物列表
    public function petslist() {
        $data = M()->field('pet.id, petname, breed, age, sex, entertime, room.name roomname')
            ->table('pet pet,room room')
            ->where("room.id=pet.roomid")
            ->order('roomname')
            ->select();
        $this->assign("list", $data);
        $this->display();
    }

    // 添加/修改宠物
    public function pet(){
        $id = I('request.id');
        if($id) { // 修改宠物
            $pet = M('pet')->where("id=%d", $id)->select();
            $old_roomid = $pet[0]['roomid'];
            $mod2 = M('room')->where("nownum<capacity")->select();
            $care_mod = M('careworker')->select();
            $care_ids = M('lookafter')->where("petid=%d", $id)->select();
            // 绑定宠物基本信息
            $this->assign("list", $pet);
            // 绑定房间
            foreach ($mod2 as &$item) {
                if($item['id'] == $old_roomid) {
                    $item['se'] = 'selected="true"';
                }
            }
            $this->assign("classify", $mod2);
            foreach ($care_mod as &$item) {
                foreach ($care_ids as $care_id) {
                    if($item['id'] == $care_id['careworkerid']) {
                        $item['check'] = 'checked="checked"';
                    }
                }
            }
            $this->assign("cares", $care_mod);
            $this->display();

            if(IS_POST) {
                $pet = M('pet');
                $pet_data['img'] = I('pp');
                $pet_data['petname'] = I('petsname');
                $pet_data['roomid'] = I('roomsclassify');
                $pet_data['breed'] = I('category');
                $pet_data['age'] = I('age');
                $pet_data['sex'] = I('sex');
                $pet_data['entertime'] = I('entertime');
                $careworkers = I('careworkers');

                // 开启事务
                $User = M();
                $User->startTrans();
                try {
                    $resultForPet = $pet->where('id=%d', $id)->save($pet_data);
                    if($old_roomid != $pet_data['roomid']) { // 更换房间
                        // 原房间nownum - 1
                        $old_room = M('room')->where("id=%d", $old_roomid)->setDec('nownum', 1);
                        // 新房间nownum + 1
                        $new_room = M('room')->where("id=%d", $pet_data['roomid'])->setInc('nownum', 1);
                    }
                    // 先删除原来的护工-宠物关系数据
                    $lookafter = M('lookafter')->where("petid=%d", $id)->delete();
                    // 添加新的数据
                    foreach ($careworkers as $careworker) {
                        $lookafter = M('lookafter');
                        $lookafter->careworkerid = $careworker;
                        $lookafter->petid = $id;
                        $lookafter->add();
                    }
                    // 提交事务
                    $User->commit();
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('修改成功');</script>";
                    $this->redirect("/Home/Index/petslist");
                } catch(Exception $e) {
                    // 事务回滚
                    $User->rollback();
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('修改失败');</script>";
                }
            }
        } else { // 新增宠物
            // 查询当前未满的房间
            $mod2 = M('room')->where("nownum<capacity")->select();
            // 查询护工列表
            $care_mod = M('careworker')->select();
            $this->assign("classify", $mod2);
            $this->assign("cares", $care_mod);
            $this->display();
            if(IS_POST) {
                $pet = M('pet');
                $pet_data['img'] = I('pp');
                $pet_data['petname'] = I('petsname');
                $pet_data['roomid'] = I('roomsclassify');
                $pet_data['breed'] = I('category');
                $pet_data['age'] = I('age');
                $pet_data['sex'] = I('sex');
                $pet_data['entertime'] = I('entertime');
                $careworkers = I('careworkers');

                // 开启事务
                $User = M();
                $User->startTrans();
                try {
                    $result = $pet->add($pet_data);
                    // 在对应的room表中, nownum + 1
                    $room = M('room')->where("id=%d", $pet_data['roomid'])->setInc('nownum', 1);
                    $pet_id = $result['id'];
                    foreach ($careworkers as $careworker) {
                        $lookafter = M('lookafter');
                        $lookafter->careworkerid = $careworker;
                        $lookafter->petid = $pet_id;
                        $lookafter->add();
                    }

                    // 提交事务
                    $User->commit();
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('添加成功');</script>";
                    $this->redirect("/Home/Index/petslist");
                } catch(Exception $e) {
                    // 事务回滚
                    $User->rollback();
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('添加失败');</script>";
                }
            }
        }
    }

    // 照料列表
    public function lookafter() {
        $data = M()->field('lookafter.id, careworker.name careworker_name, pet.petname pet_name')
            ->table('pet pet, careworker careworker, lookafter lookafter')
            ->where('pet.id=lookafter.petid AND careworker.id=lookafter.careworkerid')
            ->order('careworker_name')
            ->select();
        $this->assign("list", $data);
        $this->display();
    }

    // 删除照料关系
    public function deletelookafter() {
        $id = I('request.id');
        $lookafter = M('lookafter');
        $result = $lookafter->where('id=%d', $id)->delete();
        if($result === false) {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo "<script>alert('删除失败');</script>";
        } else if($result === 0) {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo "<script>alert('没有删除任何记录');</script>";
        } else {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo "<script>alert('删除成功');</script>";
            $this->redirect("/Home/Index/lookafter");
        }
    }

    // 护工列表
    public function careworkers() {
        $careworkers = M('careworker');
        $vo = $careworkers->select();
        $this->assign("list", $vo);
        $this->display();
    }

    // 添加/修改护工
    public function careworker() {
        $id = I('request.id');
        if($id) { // 修改护工信息
            $careworker = M('careworker');
            $vo = $careworker->where('id=%d', $id)->find();
            $this->assign("list", $vo);
            $this->display();
            if(IS_POST) {
                if(isset($_POST['save'])) {
                    $careworker = M('careworker');
                    $careworker->id = $id;
                    $careworker->name = $_POST['name'];
                    $careworker->sex = $_POST['sex'];
                    $careworker->phone = $_POST['phone'];
                    $careworker->idcard = $_POST['idcard'];
                    $careworker->address = $_POST['address'];

                    $test = M('careworker')->where("idcard='%s'", $careworker->idcard)->find();
                    if(count($test)) {
                        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                        echo "<script>alert('身份证重复');</script>";
                        return;
                    }
                    $result = $careworker->save();
                    if($result !== false) {
                        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                        echo "<script>alert('修改成功');</script>";
                        $this->redirect("/Home/Index/careworkers");
                    } else {
                        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                        echo "<script>alert('修改失败');</script>";
                    }
                }
            }
        } else {
            $this->display();
            if(isset($_POST['save'])) {
                $careworker = M('careworker');
                $careworker->name = $_POST['name'];
                $careworker->sex = $_POST['sex'];
                $careworker->phone = $_POST['phone'];
                $careworker->idcard = $_POST['idcard'];
                $careworker->address = $_POST['address'];

                $test = M('careworker')->where("idcard='%s'", $careworker->idcard)->find();
                if(count($test)) {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('身份证重复');</script>";
                    return;
                }
                $result = $careworker->add();
                if($result) {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('添加成功');</script>";
                    $this->redirect("/Home/Index/careworkers");
                } else {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('添加失败');</script>";
                }
            }
        }
    }

    // 用户列表
    public function users() {
        $user = M('user');
        $vo = $user->select();
        $this->assign("list", $vo);
        $this->display();
    }

    // 修改用户信息
    public function user() {
        $id = I('request.id');
        $users = M('user');
        $vo = $users->where('id=' . $id)->select();
        $this->assign("list", $vo);
        $this->display();
        if (IS_POST) {
            if (isset($_POST['save'])) {
                $user = M('user');
                $user->id = $id;
                $user->username = $_POST['username'];
                $user->password = md5($_POST['password']);
                $user->realname = $_POST['realname'];
                $user->sex = $_POST['sex'];
                $user->idcard = $_POST['idcard'];
                $user->phone = $_POST['phone'];
                $user->address = $_POST['address'];
                $result = $user->save();
                if ($result) {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('修改成功');</script>";
                    $this->redirect("/Home/Index/users");
                } else {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo '<script type="text/javascript">alert("修改失败")</script>';
                }
            }
        }
    }

    //删除用户
    public function deleteuser() {
        $id = I('request.id');
        $user = M('user');
        $result = $user->delete($id);
        if ($result) {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo "<script>alert('删除成功');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
        } else {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo "<script>alert('删除失败');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
        }
    }


    // 管理员列表
    public function admins() {
        $admin = M('admin');
        $vo = $admin->select();
        $this->assign("list", $vo);
        $this->display();
    }

    //管理员信息
    public function admin() {
        $adminaccount = $_SESSION['adminaccount'];
        $admin = M('admin')->where("adminaccount='" . $adminaccount . "'")->select();
        if (true) {
            $id = I('request.id');
            $admin = M('admin');
            $vo = $admin->where('id=' . $id)->select();
            $this->assign("list", $vo);
            $this->assign("id", $id);
            $this->display();
            if (IS_POST) {
                $admin = M('admin');
                $admin->id = $id;
                $admin->adminname = $_POST['name'];
                $admin->adminaccount = $_POST['account'];
                $password = $_POST['password'];
                //采用md5加密
                $admin->password = md5($password);
                $result = $admin->save();
                if ($result) {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo "<script>alert('修改成功');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
                } else {
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo '<script type="text/javascript">alert("修改失败")</script>';
                }
            }
        }
    }

}