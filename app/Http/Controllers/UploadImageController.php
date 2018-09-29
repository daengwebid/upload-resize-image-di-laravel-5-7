<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Image_uploaded;
use Carbon\Carbon;
use Image;
use File;

class UploadImageController extends Controller
{
    public $path;
    public $dimensions;

    public function __construct()
    {
        $this->path = storage_path('app/public/images');
        $this->dimensions = ['245', '300', '500'];
    }

    public function upload(Request $request)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpg,png,jpeg'
        ]);

        if (!File::isDirectory($this->path)) {
            File::makeDirectory($this->path);
        }

        $file = $request->file('image');
        $fileName = Carbon::now()->timestamp . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        Image::make($file)->save($this->path . '/' . $fileName);

        foreach ($this->dimensions as $row) {
            $canvas = Image::canvas($row, $row);
            $resizeImage  = Image::make($file)->resize($row, $row, function($constraint) {
                $constraint->aspectRatio();
            });

            if (!File::isDirectory($this->path . '/' . $row)) {
                File::makeDirectory($this->path . '/' . $row);
            }
        
            $canvas->insert($resizeImage, 'center');
            $canvas->save($this->path . '/' . $row . '/' . $fileName);
        }

        Image_uploaded::create([
            'name' => $fileName,
            'dimensions' => implode('|', $this->dimensions),
            'path' => $this->path
        ]);
        return redirect()->back()->with(['success' => 'Gambar Telah Di-upload']);
    }
}
