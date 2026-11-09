<?php
namespace App\Services\Signage;
use App\Models\SignageMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
class SignageMediaProcessor {
    public function process(int $id): void {
        $media=SignageMedia::findOrFail($id);
        if(!SignageMedia::whereKey($id)->whereIn('status',['queued','failed'])->update(['status'=>'processing','error'=>null]))return;
        $media->refresh();
        $disk=Storage::disk('local');$source=$disk->path($media->original_path);
        $base='signage/derived/'.$id.'-'.bin2hex(random_bytes(8));$disk->makeDirectory('signage/derived');
        try {
            if(str_starts_with($media->mime,'image/')){
                $size=@getimagesize($source);
                if(!$size||$size[0]*$size[1]>40000000)throw new \RuntimeException('image');
                $input=@imagecreatefromstring(file_get_contents($source));
                if(!$input)throw new \RuntimeException('image');
                $ratio=min(1,1920/$size[0],1080/$size[1]);$width=max(1,(int)round($size[0]*$ratio));$height=max(1,(int)round($size[1]*$ratio));
                $output=imagecreatetruecolor($width,$height);imagealphablending($output,false);imagesavealpha($output,true);
                imagecopyresampled($output,$input,0,0,0,0,$width,$height,$size[0],$size[1]);
                $path=$base.'.png';if(!imagepng($output,$disk->path($path)))throw new \RuntimeException('write');
                imagedestroy($input);imagedestroy($output);
                $data=['path'=>$path,'poster_path'=>$path,'mime'=>'image/png','width'=>$width,'height'=>$height,'duration'=>null];
            }else{
                $probe=new Process([config('signage.ffprobe'),'-v','error','-show_format','-show_streams','-of','json',$source]);
                $probe->setTimeout(30);$probe->mustRun();$meta=json_decode($probe->getOutput(),true,512,JSON_THROW_ON_ERROR);
                $streams=array_values(array_filter($meta['streams']??[],fn($s)=>($s['codec_type']??'')==='video'));
                $duration=(float)($meta['format']['duration']??0);
                if(!$streams||$duration<=0||$duration>(int)config('signage.video_max_seconds')||($streams[0]['width']??0)>8192||($streams[0]['height']??0)>8192)throw new \RuntimeException('video');
                $path=$base.'.mp4';$poster=$base.'.jpg';
                $convert=new Process([config('signage.ffmpeg'),'-nostdin','-v','error','-y','-i',$source,'-map','0:v:0','-an',
                    '-vf','scale=w=min(1920\\,iw):h=min(1080\\,ih):force_original_aspect_ratio=decrease:force_divisible_by=2',
                    '-c:v','libx264','-preset','veryfast','-crf','23','-pix_fmt','yuv420p','-r','30','-movflags','+faststart',$disk->path($path)]);
                $convert->setTimeout(1800);$convert->mustRun();
                $thumb=new Process([config('signage.ffmpeg'),'-nostdin','-v','error','-y','-i',$disk->path($path),'-frames:v','1','-vf','scale=480:-1',$disk->path($poster)]);
                $thumb->setTimeout(60);$thumb->mustRun();
                $normalized=new Process([config('signage.ffprobe'),'-v','error','-show_streams','-of','json',$disk->path($path)]);
                $normalized->setTimeout(30);$normalized->mustRun();$v=json_decode($normalized->getOutput(),true,512,JSON_THROW_ON_ERROR)['streams'][0];
                $data=['path'=>$path,'poster_path'=>$poster,'mime'=>'video/mp4','width'=>(int)$v['width'],'height'=>(int)$v['height'],'duration'=>$duration];
            }
            $media->update($data+['status'=>'ready','bytes'=>$disk->size($data['path']),'checksum'=>hash_file('sha256',$disk->path($data['path']))]);
        }catch(\Throwable $e){
            // Keep diagnostics free of paths, command lines and uploaded file contents.
            $media->update(['status'=>'failed','error'=>'A média feldolgozása nem sikerült. Ellenőrizd a fájlt és az FFmpeg/worker működését.']);
        }
    }
}
