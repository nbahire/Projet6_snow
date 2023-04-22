<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @throws \Exception
     */
    public function add(UploadedFile $picture, ?string $folder = '', ?int $width = 250, ?int $height = 250): string
    {
        // On donne un nouveau nom a l'image
        $file = md5(uniqid(rand(), true)).'.jpg';

        // On recupere les infos de l'image
        $pictureData = getimagesize($picture);

        if (false === $pictureData) {
            throw new \Exception('Format d\'images incorrecte');
        }

        // On verifie le format
        $pictureSource = match ($pictureData['mime']) {
            'image/png' => imagecreatefrompng($picture),
            'image/jpeg' => imagecreatefromjpeg($picture),
            'image/webp' => imagecreatefromwebp($picture),
            default => throw new \Exception('Format d\'images incorrecte'),
        };

        // On recadre l'image
        $pictureWidth = $pictureData[0];
        $pictureHeight = $pictureData[1];

        // On verifie l'orientation de l'image
        switch ($pictureWidth <=> $pictureHeight) {
            case -1: // Portrait
                $squareSize = $pictureWidth;
                $src_x = 0;
                $src_y = ($pictureHeight - $squareSize) / 2;
                break;
            case 0: // Carré
                $squareSize = $pictureWidth;
                $src_x = 0;
                $src_y = 0;
                break;
            case 1: // Paysage
                $squareSize = $pictureHeight;
                $src_x = ($pictureWidth - $squareSize) / 2;
                $src_y = 0;
                break;
        }

        // On crée une nouvelle image
        $resizedPicture = imagecreatetruecolor($width, $height);

        imagecopyresampled($resizedPicture, $pictureSource, 0, 0, $src_x, $src_y, $width, $height, $squareSize, $squareSize);

        $path = $this->params->get('images_directory').$folder;

        // On crée le dossier de destination
        if (!file_exists($path.'/mini/')) {
            mkdir($path.'/mini/', 0755, true);
        }

        // On stocke l'image
        imagewebp($resizedPicture, $path.'/mini/'.$width.'x'.$height.'-'.$file);

        $picture->move($path.'/', $file);

        return $file;
    }

    public function delete(string $file, ?string $folder = '', ?int $width = 250, ?int $height = 250): bool
    {
        if ('default.webp' !== $file) {
            $success = false;
            $path = $this->params->get('images_directory').$folder;
            $mini = $path.'/mini/'.$width.'x'.$height.'-'.$file;
            if (file_exists($mini)) {
                unlink($mini);
                $success = true;
            }

            $originalPath = $path.'/'.$file;
            if (file_exists($originalPath)) {
                unlink($originalPath);
                $success = true;
            }

            return $success;
        }

        return false;
    }
}
