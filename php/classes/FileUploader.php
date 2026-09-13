<?php
class FileUploader
{
    private $allowedExtensions = ['jpeg','jpg','png'];
    private $allowedMime = ['image/jpeg','image/jpg','image/png'];

    public function handle(array $file): string
    {
        if (!isset($file['name']) || !isset($file['tmp_name'])) {
            throw new InvalidArgumentException('No file provided');
        }

        $img_name = $file['name'];
        $tmp_name = $file['tmp_name'];
        $img_type = $file['type'];

        $ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions, true)) {
            throw new RuntimeException('Invalid file extension');
        }
        if (!in_array($img_type, $this->allowedMime, true)) {
            throw new RuntimeException('Invalid mime type');
        }

        $newName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $img_name);
        // New storage location: project data/user directory (moved from php/images)
        $destinationDir = __DIR__ . '/../../data/user/';
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        $destination = $destinationDir . $newName;

        if (!move_uploaded_file($tmp_name, $destination)) {
            throw new RuntimeException('Failed to move uploaded file');
        }

        return $newName;
    }
}

?>