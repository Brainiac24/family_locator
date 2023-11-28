<?php

namespace App\Service;

class ImagesService
{
    final public function saveUserAvatar(int $userId): string
    {
        $target_dir = 'avatars';

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!file_exists($target_dir . '/' . $userId)) {
            mkdir($target_dir . '/' . $userId, 0777, true);
        }

        $target_dir = $target_dir . '/' . $userId . '/avatar' . $userId . '.jpg';

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $target_dir)) {
            return false;
        }

        return $target_dir;
    }
}
