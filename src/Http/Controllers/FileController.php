<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Traits\HasUploadedFile;

class FileController
{
    use HasUploadedFile;

    public function handle()
    {
        $disk = $this->disk(config('bpm.upload.disk'));

        // 判断是否是删除文件请求
        if ($this->isDeleteRequest()) {
            // 删除文件并响应
            return $this->deleteFileAndResponse($disk);
        }

        // 获取上传的文件
        $file = $this->file();

        // 获取上传的字段名称
        $column = $this->uploader()->upload_column;

        $dir = request('apps') . '/' . request('form');

        $newName = $column . request('name', md5($file->getFilename()));

        $result = $disk->putFileAs($dir, $file, $newName);

        $path = "{$dir}/$newName";

        return $result
            ? $this->responseUploaded($path, $disk->url($path))
            : $this->responseErrorMessage('文件上传失败');
    }
}
