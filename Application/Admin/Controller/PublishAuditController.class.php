<?php
/**
 * 发布审核
 * User: 李胜辉
 * Date: 2018/10/17
 * Time: 17:32
 */
namespace Admin\Controller;


class PublishAuditController extends BaseController {
    public function index()
    {
        $this->display();
    }

    /**
     * ajax列表页
     * @author: 李胜辉
     * @time: 2018/10/25 17:32
     */
    public function ajaxGetIndex()
    {
        $getInfo = I('post.');
        $curr = $getInfo['curr'] ? $getInfo['curr'] : 1;//当前页
        $limit = $getInfo['limit'] ? $getInfo['limit'] : 1;//每页显示条数
        $start = ($curr-1) * $limit;//开始
        $com_name = $getInfo['com_name'] ? $getInfo['com_name'] : '';//查询关键字
        $add_time = $getInfo['add_time'] ? strtotime($getInfo['add_time']) : '';//查询的时间
        $audit_status = $getInfo['audit_status'] ? $getInfo['audit_status'] : '';//查询的审核状态
        $where = array();
        if ($add_time != '') {
            $big_time = $add_time + 24 * 60 * 60;
            $where['p.pub_time'] = array(array('elt', $big_time),array('egt', $add_time));
        }
        if ($audit_status != '') {
            $where['p.audit_status'] = $audit_status;
        }
        if ($com_name != '') {
            $where['s.com_name'] = array('like','%'.$com_name.'%');
        }
        /*var_dump($where);exit;*/
        //查询总条数
        $total = D('api_publish as p')->join('left join api_ct_users as s on s.id=p.pub_userid')->join('left join api_user as u on u.id=p.audit_userid')->where($where)->count();//查询满足要求的总记录数

        $info = D('api_publish as p')->join('left join api_ct_users as s on s.id=p.pub_userid')->join('left join api_user as u on u.id=p.audit_userid')->field('p.id,s.com_name,p.title,p.intro,p.pub_time,u.username,p.audit_time,p.audit_status')->where($where)->order('p.id')->limit($start, $limit)->select();
        foreach($info as $keys=>$values){

            foreach($values as $key=>$value){

                if($values[$key]===null){
                    $info[$keys][$key] = '';

                }
            }

        }
        if($info){
            $data = array(
                'limit'=>$limit,
                'curr'=>$curr,
                'add_time'=>$getInfo['add_time'],
                'audit_status'=>$audit_status,
                'status'=>'success',//查询状态:成功为success,失败为fail
                'total' => $total,
                'data' => $info
            );
        }else{
            $data = array(
                'limit'=>$limit,
                'curr'=>$curr,
                'add_time'=>$getInfo['add_time'],
                'audit_status'=>$audit_status,
                'status'=>'fail',
                'total' => $total,
                'data' => $info
            );
        }

        $this->ajaxReturn($data, 'json');
    }

    /**
     * 审核通过
     * @author: 李胜辉
     * @time: 2018/10/25 14:32
     */
    public function open()
    {
        $id = I('post.id');
        $arr['audit_status'] = 'S';
        $arr['audit_userid'] = session('uid');
        $arr['audit_time'] = time();
        $res = D('api_publish')->where(array('id' => $id))->save($arr);
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }

    /**
     * 拒绝通过
     * @author: 李胜辉
     * @time: 2018/10/23 09:32
     */
    public function close()
    {
        $id = I('post.id');
        $arr['audit_status'] = 'F';
        $arr['audit_userid'] = session('uid');
        $arr['audit_time'] = time();
        $res = D('api_publish')->where(array('id' => $id))->save($arr);
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('添加成功');
        }
    }
    /**
     * 查看
     * @author: 李胜辉
     * @time: 2018/10/25 09:32
     */
    public function look()
    {
        $id = I('get.id');
        $catalogList = D('api_publish_content as c')->join('left join api_publish as p on p.id=c.publish_id')->join('left join api_ct_users as s on s.id=c.pub_userid')->field('c.title,c.id,p.id as pid,p.title as ptitle')->where(array('c.publish_id'=>$id))->select();
        $total = count($catalogList);
        $this->assign('catalogList', $catalogList);
        $this->assign('total', $total);
        $this->display();
    }
    /**
     * ajax查看
     * @author: 李胜辉
     * @time: 2018/10/25 09:32
     */
    public function ajaxLook()
    {
        $id = I('get.id');
        $list = D('api_publish as p')->join('left join api_publish_content as c on c.publish_id=p.id')->join('left join api_ct_users as s on s.id=p.pub_userid')->field('p.title,p.price,p.cover,p.read_num,p.collect_num,p.share_num,p.user_type,p.item_type,p.intro,c.id,c.title,s.user_name,p.pub_time')->where(array('p.id' => $id))->find();
        $this->assign('list', $list);
        $this->display();
    }
    /**
     * ajax查看目录
     * @author: 李胜辉
     * @time: 2018/10/25 09:32
     */
    public function ajaxLookCatalog()
    {
        $id = I('get.id');

        $list = D('api_publish as p')->join('left join api_publish_content as c on c.publish_id=p.id')->join('left join api_ct_users as s on s.id=p.pub_userid')->field('p.title,p.price,p.cover,p.read_num,p.collect_num,p.share_num,p.user_type,p.item_type,p.intro,c.id,c.title,s.user_name,p.pub_time')->where(array('p.id' => $id))->find();
        $this->assign('list', $list);
        $this->display();
    }
    /**
     * 查看目录详情
     * @author: 李胜辉
     * @time: 2018/10/25 09:32
     */
    public function lookCatalog()
    {
        $id = I('get.id');

        $list = D('api_publish_content as c')->join('left join api_publish as p on p.id=c.publish_id')->join('left join api_ct_users as s on s.id=c.pub_userid')->field('p.title,c.price,c.title,c.content,c.read_num,c.share_num,c.collect_num,c.pub_time,s.user_name')->where(array('c.id' => $id))->find();
        $this->assign('list', $list);
        $this->display();
    }
    /**
     * 图片上传
     * @author: 李胜辉
     * @time: 2018/10/17 17:32
     */
    public function upload()
    {
        //获取网站根目录地址$url
        $PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $str = substr($PHP_SELF, 1);
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . substr($str, 0, strpos($str, '/') + 1);
        if (!empty($_FILES)) {
            $upload = new \Think\Upload();   // 实例化上传类
            $upload->maxSize = 3145728;    // 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $upload->rootPath = THINK_PATH;          // 设置附件上传根目录
            $upload->savePath = '../Public/';    // 设置附件上传（子）目录
            $upload->subName = 'uploads/articlePublish/knowledge';  //子文件夹
            $upload->replace = true;  //同名文件是否覆盖
            // 上传文件
            $images = $upload->upload();
            //return $images;
            //Log::record('$images', Log::DEBUG);die;
            //判断是否有图
            if ($images) {
                $info['code'] = 0;//成功
                $info['msg'] = "";//信息
                $info['data']['src'] = $url . substr($images['file']['savepath'], 3) . $images['file']['savename'];//拼接图片地址
                echo json_encode($info);
            } else {
                $info['code'] = 1;//失败
                $info['msg'] = "fail";//信息
                $info['data']['src'] = $url . substr($images['file']['savepath'], 3) . $images['file']['savename'];//拼接图片地址
                echo json_encode($info);
            }
        } else {
            echo json_encode(2);
        }
    }


}