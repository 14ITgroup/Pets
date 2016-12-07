<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }

    public function appLogin() {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $password = md5($password);

        $user = M("user");
        $data = $user->where("username='%s' AND password='%s'", $username, $password)->find();
        if($data) {
            echo $data['id'];
        } else {
            echo "0";
        }
    }

    // app用户注册
    public function appRegister(){
        $username = I('username');
        $password = I('password');
        $password = md5($password);
        $realname = I('realname');
        $sex = I('sex');
        $idcard = I('idcard');
        $phone = I('phone');
        $address = I('address');

        $user = M('user');
        $result = $user->where("username='%s'", $username)->find();
        if($result) {
            echo "0";
            return; // 已存在该用户
        } else {
            $user->username = $username;
            $user->password = $password;
            $user->realname = $realname;
            $user->sex = $sex;
            $user->idcard = $idcard;
            $user->phone = $phone;
            $user->address = $address;
            //$user->badrecord="";

            $result = $user->add();
            echo $result;
        }
    }

    // support the data for the PetsActivity
    public function appGetPets(){
        $pet = M('pet');
        // find 方法只会返回第一条记录
        $data=$pet->select();
        $this->ajaxReturn($data,'json');
    }

    // deal with the application of getting a pet home
    // 处理用户申请领养宠物
    public function appDealApplication(){
        $userid = I('user_id');
        $petid = I('pet_id');
        $apply = M('application');

        $repeat = $apply->where('userid=%d AND petid=%d AND ispass<>-1', $userid, $petid)->select();
        if($repeat) {
            // 已申请待审核或者已经领养了该宠物
            echo "-1";
            return;
        }

        $repeat = $apply->where('userid=%d AND ispass=1', $userid, $petid)->select();
        if(count($repeat) >= 3) {
            // 同一用户不能同时领养超过3只宠物
            echo "-2";
            return;
        }

        $repeat = $apply->where('userid=%d AND ispass=0', $userid)->select();
        if(count($repeat) >= 5) {
            // 同一用户不能同时申请超过5只宠物
            echo "-3";
            return;
        }

        $apply = M('application');
        $apply->userid = $userid;
        $apply->petid = $petid;
        $result = $apply->add();
        if($result) {
            // 申请成功, 等待审核
            echo "1";
            return;
        }
    }

    /* return the user's application
     查看用户的申请信息(包括已被审核的和未审核的)
     请求信息: user_id
     返回信息: 宠物信息 与 申请通过与否
    */
    public function appGetWaiting(){
        $userid = I('user_id');

        $data = M()->table('pet pet, application apply')
            ->where('userid=%d AND pet.id=apply.petid', $userid)
            ->select();

        $this->ajaxReturn($data, 'json');
    }

    // 根据app发送的id返回对应的宠物信息
    public function appGetPetById() {
        $id = I('pet_id');
        $pet = M('pet');

        $data = $pet->where("id='%d'", $id)->select();
        $this->ajaxReturn($data, "json");
    }


}