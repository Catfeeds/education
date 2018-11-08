<?php

/**
 * 商城相关操作
 * @since   2018/11/07 创建
 * @author  李胜辉
 */

namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Crypt;
use Home\ORG\Response;
use Home\ORG\ReturnCode;

class Store extends Base
{


    /**
     * 轮播图
     * @author: 李胜辉
     * @time: 2018/11/07 10:34
     *
     */
    public function slideshow($param)
    {
        $pic_type = param['pic_type'];
        $list = D('api_slideshow as s')->join('left join api_publish as p on p.id=s.publish_id')->field('s.id,s.publish_id,p.cover')->where(array('pic_type' => $pic_type))->order('id desc')->select();
        if (empty($list)) {
            Response::error(ReturnCode::INVALID, '暂无数据');
        }
        return $list;

    }

    /**
     * 发布信息列表
     * @author: 李胜辉
     * @time: 2018/11/07 10:34
     *
     */
    public function publishList($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $user_type = $param['user_type'] ? $param['user_type'] : '';//发布人的类型(COM:机构;TEA:老师;STU:学生)
        $item_type = $param['item_type'] ? $param['item_type'] : '';//发布内容类型(ART:文章;RAD:视频;SRAD:系列视频;FILE:文件;PIC:图片;)
        $where = array();
        $where['del_status'] = 2;
        $where['audit_status'] = 'S';
        $where['user_type'] = $user_type;
        if ($item_type !== '') {
            $where['item_type'] = $item_type;
        }
        if ($user_type == 'TEA' || $user_type == 'COM') {
            $list = D('api_publish as p')->join('left join api_ct_users as c on c.id=p.pub_userid')->join('left join api_publish_category as pc on pc.id=p.category_id')->field('p,id,p.title,p.pub_time,p.share_num,p.collect_num,p.cover,c.icon,c.user_name,pc.category_name')->where($where)->order('p.id desc')->limit($start, $limit)->select();
            $list['response_status'] = 'success';//success:成功;fail:失败
        } else if ($user_type == 'STU') {
            $list = D('api_publish as p')->join('left join api_users as c on c.id=p.pub_userid')->join('left join api_publish_category as pc on pc.id=p.category_id')->field('p,id,p.title,p.pub_time,p.share_num,p.collect_num,p.cover,c.icon,c.user_name,pc.category_name')->where($where)->order('p.id desc')->limit($start, $limit)->select();
            $list['response_status'] = 'success';//success:成功;fail:失败
        } else {
            $list['response_status'] = 'fail';//success:成功;fail:失败
        }

        return $list;
    }

    /**
     * 发布信息详情页
     * @author: 李胜辉
     * @time: 2018/11/07 10:34
     *发布内容类型(ART:文章;RAD:视频;SRAD:系列视频;FILE:文件;PIC:图片;)
     */
    public function publishDetail($param)
    {
        $id = $param['id'];
        $pub_type = $param['user_type'];
        $info = D('api_publish')->where(array('id' => $id,'del_status'=>2,'audit_status'=>'S'))->field('id,item_type,pub_time,price')->find();
        $item_type = $info->item_type;
        if ($item_type == 'SRAD') { //系列视频
            switch ($pub_type) {
                case 'STU':
                    $list = D('api_publish_content as c')->join('left join api_users as u on u.id=p.pub_userid')->field('c.id,c.content,c.title,u.icon,u.user_name,u.nickname,c.pub_userid')->where(array('c.publish_id' => $id,'c.del_status'=>2,'c.audit_status'=>'S'))->order('c.id asc')->limit(0, 1)->select();
                    $list['pub_time'] = $info->pub_time;
                    $list['price'] = $info->price;
                    if($list){
                        $list['response_status'] = 'success';//成功
                        return $list;
                    }else{
                        $list['response_status'] = 'fail';//失败
                        return $list;
                    }
                    break;
                case 'TEA':
                    break;
                case 'COM':
                    $list = D('api_publish_content as c')->join('left join api_ct_users as u on u.id=p.pub_userid')->field('c.id,c.content,c.title,u.icon,u.user_name,u.com_name,u.nickname,c.pub_userid')->where(array('c.publish_id' => $id,'c.del_status'=>2,'c.audit_status'=>'S'))->order('c.id asc')->limit(0, 1)->select();
                    $list['pub_time'] = $info->pub_time;
                    $list['price'] = $info->price;
                    if($list){
                        $list['response_status'] = 'success';//成功
                        return $list;
                    }else{
                        $list['response_status'] = 'fail';//失败
                        return $list;
                    }
                    break;
                default :
                    $list['response_status'] = 'fail';//失败
                    return $list;
                    break;
            }
        } else {
            switch ($pub_type) { //非系列视频
                case 'STU':
                    $list = D('api_publish as p')->join('left join api_publish_content as c on c.publish_id=p.id')->join('left join api_users as u on u.id=p.pub_userid')->field('p.id,p.title,u.icon,u.user_name,u.nickname,p.pub_userid,p.pub_time,c.content')->where(array('c.publish_id' => $id,'c.del_status'=>2,'c.audit_status'=>'S'))->select();
                    if($list){
                        $list['response_status'] = 'success';//成功
                        return $list;
                    }else{
                        $list['response_status'] = 'fail';//失败
                        return $list;
                    }
                    break;
                case 'TEA':
                case 'COM':
                    $list = D('api_publish as p')->join('left join api_publish_content as c on c.publish_id=p.id')->join('left join api_ct_users as u on u.id=p.pub_userid')->field('p.id,p.title,u.icon,u.user_name,u.com_name,u.nickname,p.pub_userid,p.pub_time,c.content')->where(array('c.publish_id' => $id,'c.del_status'=>2,'c.audit_status'=>'S'))->select();
                if($list){
                    $list['response_status'] = 'success';//成功
                    return $list;
                }else{
                    $list['response_status'] = 'fail';//失败
                    return $list;
                }
                    break;
                default :
                    $list['response_status'] = 'fail';//失败
                    return $list;
                    break;
            }
        }


    }
    /**
     * 视频目录列表
     * @author: 李胜辉
     * @time: 2018/11/07 10:34
     *发布内容类型(ART:文章;RAD:视频;SRAD:系列视频;FILE:文件;PIC:图片;)
     */
    public function videoCatalogList($param)
    {
        $publish_id = $param['publish_id'];
        $limit = $param['limit'];//每页显示条数
        $total = D('api_publish_content')->where(array('publish_id'=>$publish_id))->count();
        $limit = $param['limit']?$param['limit']:$total;//每页显示条数
        $list = D('api_publish_content')->field('id,title,duration')->where(array('publish_id'=>$publish_id))->order('id asc')->limit(0,$limit)->select();
        if($list){
            $list['response_status'] = 'success';//成功
            return $list;
        }else{
            $list['response_status'] = 'fail';//失败
            return $list;
        }

    }
    /**
     * 视频详情页
     * @author: 李胜辉
     * @time: 2018/11/07 10:34
     *发布内容类型(ART:文章;RAD:视频;SRAD:系列视频;FILE:文件;PIC:图片;)
     */
    public function videoCatalogDetail($param)
    {
        $id = $param['id'];
        $detail = D('api_video_content')->field('id,title,content')->where(array('id'=>$id,'del_status'=>2,'audit_status'=>'S'))->find();

        if($detail){
            $detail['response_status'] = 'success';//成功
            return $detail;
        }else{
            $detail['response_status'] = 'fail';//失败
            return $detail;
        }

    }
    /**
     * 热门评论列表
     * @author: 李胜辉
     * @time: 2018/11/07 10:34
     */
    public function hotComment($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $item_id = $param['item_id'];//评论标题id
        $pub_type = $param['pub_type'];//发布内容用户类型(COM:机构;TEA:老师;STU:学生)
        if($pub_type=='STU'){
            $list = D('api_comment as c')->join('left join api_users as u on u.id=c.user_id')->field('u.icon,u.nickname,u.user_name,c.content,c.add_time')->where(array('c.item_id'=>$item_id,'c.audit_status'=>'S'))->limit($start,$limit)->order('c.id desc')->select();

        }else{
            $list = D('api_comment as c')->join('left join api_ct_users as u on u.id=c.user_id')->field('u.icon,u.com_name,u.nickname,u.user_name,c.content,c.add_time')->where(array('c.item_id'=>$item_id,'c.audit_status'=>'S'))->limit($start,$limit)->order('c.id desc')->select();

        }
        if($list){
            $list['response_status'] = 'success';//成功
            return $list;
        }else{
            $list['response_status'] = 'fail';//失败
            return $list;
        }

    }
    /**
     * 发布信息分类列表
     * @author: 李胜辉
     * @time: 2018/11/07 10:34
     */
    public function publishCategoryList()
    {
        $list = D('api_publish_category')->order('id asc')->select();
        if($list){
            $list['response_status'] = 'success';//成功
            return $list;
        }else{
            $list['response_status'] = 'fail';//失败
            return $list;
        }

    }
    /**
     * 发布图片/文章/视频/文件
     * @author: 李胜辉
     * @time: 2018/11/08 10:34
     */
    public function publishInfo($param)
    {
        $item_type = $param['item_type'];//发布内容类型(ART:文章;VID:视频;SVID:系列视频;FILE:文件;PIC:图片;)
        switch ($item_type){
            case 'ART':
                $path = 'publish/article';
                break;
            case 'VID':
                $path = 'publish/video';
                break;
            case 'FILE':
                $path = 'publish/file';
                break;
            case 'PIC':
                $path = 'publish/pic';
                break;
            default :
                $path = 'publish/article';
                break;
        }
        $url = $this->uploads($path);
        $data['cover'] = $param['cover']?$param['cover']:'';//封面
        $data['title'] = $param['title'];//标题
        $data['intro']= $param['content']?$param['content']:'';//内容
        $data['pub_time'] = time();//添加时间
        $data['category_id']= $param['category_id'];//类型
        $data['user_type']= $param['user_type'];//发布人的类型(COM:机构;TEA:老师;STU:学生)
        $data['item_type']= $param['item_type'];//发布内容类型(ART:文章;RAD:视频;SRAD:系列视频;FILE:文件;PIC:图片;)
        $data['pub_userid'] = $param['user_id'];//发布人id
        $insert = D('api_publish')->add($data);
        if($insert){
            $content['cover'] = $param['cover']?$param['cover']:'';//封面
            $content['publish_id'] = $insert;//发布id
            $content['title'] = $param['title'];//标题
            $content['content']= $url;//内容
            $content['pub_time'] = time();//添加时间
            $content['pub_userid'] = $param['user_id'];//发布人id
            $add = D('api_publish_content')->add($content);
            if($add){
                return array('response_status' => 'success');//成功
            }else{
                return array('response_status' => 'half');//目录表没有添加成功
            }

        }else{
            return array('response_status' => 'fail');//失败
        }
    }


    /**
     * 发布系列视频
     * @author: 李胜辉
     * @time: 2018/11/07 16:34
     */
    public function publishSetVideo($param)
    {
        $path = 'publish/setVideo';
        $file_name = array();
        //将视频url保存到数组中
        if($_FILES){
            foreach($_FILES as $key=>$value){
                $url = $this->uploads($path);
                $file_name[] = $url;
            }
            unset($key,$value);
        }

        $data['cover'] = $param['cover']?$param['cover']:'';//封面
        $data['title'] = $param['title'];//标题
        $data['intro']= $param['content']?$param['content']:'';//内容
        $data['pub_time'] = time();//添加时间
        $data['category_id']= $param['category_id'];//类型
        $data['user_type']= $param['user_type'];//发布人的类型(COM:机构;TEA:老师;STU:学生)
        $data['item_type']= $param['item_type'];//发布内容类型(ART:文章;RAD:视频;SRAD:系列视频;FILE:文件;PIC:图片;)
        $data['pub_userid'] = $param['user_id'];//发布人id
        $data['price'] = $param['price'];//价格
        $insert = D('api_publish')->add($data);
        $content = array();
        $datas = array();
        if($insert){
            $content['cover'] = $param['cover']?$param['cover']:'';//封面
            $content['publish_id'] = $insert;//发布id
            $content['pub_time'] = time();//添加时间
            $content['pub_userid'] = $param['user_id'];//发布人id
            if($file_name){
                foreach($file_name as $keys=>$values){
                    $content['title'] = $param['title'.$keys];//标题
                    $content['content']= $values;//内容
                    $datas[] = $content;
                }
                unset($keys,$values);
            }
            $add = D('api_publish_content')->addAll($datas);
            if($add){
                return array('response_status' => 'success');//成功
            }else{
                return array('response_status' => 'half');//目录表没有添加成功
            }

        }else{
            return array('response_status' => 'fail');//失败
        }
    }
    /**
     * 文件上传方法
     * @author: 李胜辉
     * @time: 2018/11/07 16:34
     * @param: $files  $_FILES
     * @param: $path string 路径
     */
    public function uploads($path)
    {
        //获取网站根目录地址$url
        $PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $str = substr($PHP_SELF, 1);
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . substr($str, 0, strpos($str, '/') + 1);
        Response::debug($url);
            $upload = new \Think\Upload();   // 实例化上传类
            $upload->maxSize = 3145728;    // 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $upload->rootPath = THINK_PATH;          // 设置附件上传根目录
            $upload->savePath = '../Public/uploads/';    // 设置附件上传（子）目录
            $upload->subName = $path;  //子文件夹
            $upload->replace = true;  //同名文件是否覆盖
            // 上传文件
            $res_info = $upload->upload();
        $info = '';
        if($res_info){
            foreach ($res_info as $key => $tepimg) {
                $info .= $url . preg_replace('/^..\//','',$tepimg['savepath']) . $tepimg['savename'] . ';';//拼接图片地址
            }
            unset($key, $tepimg);
            $info = substr($info, 0, -1);
            return $info;
        }else{
            return $info;
        }
    }


}