<?php

namespace App\Http\Controllers;

use App\Util\FfmpegUtil;
use App\Util\Random;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private $USER="lindakai";
    private $PASS="20090928";

    public function login(Request $request){
        $name=$request->input("name","");
        $pass=$request->input("pass","");
        if($name===$this->USER&&$pass===$this->PASS){
            session(["admin"=>$name]);
            return redirect("/admin/home");
        }else{
            return redirect("/admin")->with("msg","用户名或密码错误");
        }
    }

    //------------------------------------------------------------------------------------------------------------------
    public function getHome(Request $request){
        //categery
        $cat_page=$request->input("cat_page",1);
        $result1=DB::select("select * from video_categery limit ? offset ?",[10,($cat_page-1)*10]);
        $result11=DB::select("select count(id) as count from video_categery");
        //
        return view("admin/home",[
            "categery"=>$result1,
            "cat_page"=>$cat_page,
            "cat_count"=>$result11[0]->count
        ]);
    }

    public function admin(Request $request){
        return response()->view("admin/login");
    }

    //
    public function addCategery(Request $request){
        $name=$request->input("name");
        if($name==null){
            return response()->json(["msg"=>"参数错误"]);
        }
        DB::insert("insert into video_categery(name) values(?)",[$name]);
        return response()->json();
    }

    public function deleteCategery(Request $request){
        $id=$request->input("id");
        if($id==null){
            return response()->json(["msg"=>"参数错误"]);
        }
        $result1=DB::select("select count(*) as count from video where categery=?",[$id]);
        if($result1[0]->count>0){
            return response()->json(["msg"=>"无法删除，该类型下有".$result1[0]->count."个视频"]);
        }
        DB::delete("delete from video_categery where id=?",[$id]);
        return response()->json();
    }

    public function deleteCategerys(Request $request){
        $id_str=$request->input("ids");
        if($id_str==null){
            return response()->json(["msg"=>"参数错误"]);
        }
        $ids=explode("-",$id_str);
        foreach ($ids as $id){
            $result1=DB::select("select count(*) as count from video where categery=?",[$id]);
            if($result1[0]->count<=0){
                DB::delete("delete from video_categery where id=?",[$id]);
            }
        }
        return response()->json();
    }

    public function renameCategery(Request $request){
        $id=$request->input("id");
        $newName=$request->input("name");
        if($id==null||$newName==null){
            return response()->json(["msg"=>"参数错误"]);
        }
        DB::update("update video_categery set name=? where id=?",[$newName,$id]);
        return response()->json();
    }

    public function uploadPoster(Request $request){
        $file = $request->file("file");
        if ($file==null||!$file->isValid()){
            return response()->json(["msg"=>"invalid"]);
        }else if($file->extension()!="png"){
            return response()->json(["msg"=>"Error Format [".$file->extension()."]"]);
        }
        //当前目录public
        $newPath="./data/video/poster/";
        $result1=DB::select("select table_seq from seq where table_name=?",["video"]);
        $newFile=$result1[0]->table_seq.".png";
        $newFilePath=$newPath.$newFile;
        if(file_exists($newFilePath)){
            unlink($newFilePath);
        }
        $file->move($newPath,$newFile);
        return response()->json(["url"=>"/".$newFilePath]);
    }

    public function uploadVideo(Request $request){
        $file = $request->file("file");
        if ($file==null||!$file->isValid()){
            return response()->json(["msg"=>"invalid"]);
        }else if($file->extension()!="mp4"){
            return response()->json(["msg"=>"Error Format [".$file->extension()."]"]);
        }
        //当前目录public
        $newPath="./data/video/mp4/";
        $result1=DB::select("select table_seq from seq where table_name=?",["video"]);
        $newFile=$result1[0]->table_seq.".mp4";
        $newFilePath=$newPath.$newFile;
        if(file_exists($newFilePath)){
            unlink($newFilePath);
        }
        $file->move($newPath,$newFile);
        //
        $frame_path="./data/video/frame/".$result1[0]->table_seq.".jpg";
        FfmpegUtil::video_frame("ffmpeg",$newFilePath,$frame_path);
        return response()->json(["url"=>"/".$frame_path]);
    }

    public function addVideo(Request $request){

    }
    //------------------------------------------------------------------------------------------------------------------
    public function getVideoManage(Request $request){

        //video
        $v_page=$request->input("v_page",1);
        $srch_cat=$request->input("srch_cat",-1);
        if($srch_cat==-1){
            $result1=DB::select("select video.*,video_categery.name as cat_name from video join video_categery on video.categery=video_categery.id ".
                "limit ? offset ?",[20,($v_page-1)*20]);
        }else{
            $result1=DB::select("select video.*,video_categery.name as cat_name from video join video_categery on video.categery=video_categery.id ".
                "where video.categery=? limit ? offset ?",[$srch_cat,20,($v_page-1)*20]);
        }
        $result11=DB::select("select count(id) as count from video");
        //categery
        $result2=DB::select("select * from video_categery");

        return view("admin/video-manage",[
            "video"=>$result1,
            "v_page"=>$v_page,
            "v_count"=>$result11[0]->count,
            "categery"=>$result2,
            "srch_cat"=>$srch_cat
        ]);
    }

    public function deleteV(Request $request){

        return response()->json(["msg"=>"ok"]);
    }

    public function deleteVs(Request $request){

        return response()->json(["msg"=>"ok"]);
    }

    public function test(Request $request){
        $re=FfmpegUtil::video_info("ffmpeg.exe","data/video/mp4/1.mp4");
        FfmpegUtil::video_frame("ffmpeg.exe","data/video/mp4/1.mp4","data/video/frame/1.jpg");
        echo substr($re["duration"],0,strpos($re["duration"],"."));
    }


}



