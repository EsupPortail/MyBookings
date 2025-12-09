<?php

namespace App\Tools;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class CatalogTool
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ){}

    /**
     * @throws \Exception
     */
    public function uploadImage($image, $filename): string
    {
        $mimeType = $image->getMimeType();
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new \RuntimeException('Type de fichier non autorisé.');
        }
        $mimeType = substr($mimeType, strpos($mimeType, '/') + 1);
        $filename = $filename.'.'.$mimeType;
        try {
            $image->move($this->projectDir . '/public/uploads', $filename);
        } catch (FileException $e) {
            throw new \Exception($e);
        }
        return $filename;
    }
}