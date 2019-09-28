<?php
namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait UploadTrait
{
    public function uploadOne(UploadedFile $uploadedFile, $folder = null, $disk = 'local', $filename = null)
    {
        $name = !is_null($filename) ? $filename : str_random(25);
        $folder = $folder == null ? '/uploads/images/' : $folder;
        $fullName = $name.'.'.$uploadedFile->getClientOriginalExtension();
        $file = $uploadedFile->storeAs($folder, $fullName, $disk);
        return ['file' => $file, 'name' => $name, 'fullName' => $fullName, 'folder' => $folder];
    }
}