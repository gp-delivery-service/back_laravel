<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use App\Models\GpCompany;
use App\Models\User;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:2048',
            'type' => 'required|string',
            'id' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        $id = $request->input('id');

        // Определим путь папки и модель
        $config = $this->getUploadConfig($type);

        if (!$config) {
            return response()->json(['error' => 'Unknown upload type'], 400);
        }

        // Сохраняем файл в нужную папку
        $folder = $config['folder'];
        $path = $file->store("uploads/$folder", 'public');

        if (!$id) {
            // Создание, без сохранения в БД
            return response()->json([
                'message' => 'Файл загружен',
                'path' => Storage::url($path),
            ]);
        }

        // Обновление сущности
        $modelClass = $config['model'];
        $field = $config['field'];

        $model = $modelClass::findOrFail($id);

        // Удалим старое изображение, если есть
        $oldPath = $model->$field;

        if ($oldPath) {
            // Преобразуем URL в относительный путь
            $relativePath = str_replace('/storage/', '', $oldPath);

            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        // Обновим путь
        $model->$field = Storage::url($path);;
        $model->save();

        return response()->json([
            'message' => 'Изображение обновлено',
            'path' => Storage::url($path),
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id'   => 'required',
        ]);

        $type = $request->input('type');
        $id = $request->input('id');

        $config = $this->getUploadConfig($type);

        if (!$config) {
            return response()->json(['error' => 'Unknown upload type'], 400);
        }

        $modelClass = $config['model'];
        $field = $config['field'];

        $model = $modelClass::findOrFail($id);
        $filePath = $model->$field;

        if ($filePath) {
            // Преобразуем абсолютный URL в относительный путь
            $relativePath = str_replace('/storage/', '', $filePath);

            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }

            // Обнуляем поле в БД
            $model->$field = null;
            $model->save();
        }

        return response()->json([
            'message' => 'Изображение удалено',
        ]);
    }

    private function getUploadConfig($type)
    {
        return match ($type) {
            'company_logo' => [
                'folder' => 'company_logos',
                'model'  => GpCompany::class,
                'field'  => 'image',
            ],
            // 'user_avatar' => [
            //     'folder' => 'user_avatars',
            //     'model'  => \App\Models\User::class,
            //     'field'  => 'avatar',
            // ],
            default => null,
        };
    }
}
