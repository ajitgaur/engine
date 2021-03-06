<?php

/**
 * Minds Proxy Image Resizer using Image Magick.
 *
 * @author brianhatchet
 */

namespace Minds\Core\Media\Proxy;

use Imagick;
use ImagickPixel;
use Minds\Traits\MagicAttributes;

class MagicResize
{
    use MagicAttributes;

    /** @var string $image */
    protected $image;

    /** @var int $size */
    protected $size;

    /** @var int $quality */
    protected $quality;

    /** @var bool $upscale */
    protected $upscale = false;

    /** @var resource $output */
    protected $output;

    /** @var string $imageFormat */
    protected $imageFormat;

    /** @var string $image */
    public function setImage($image)
    {
        if (!$image) {
            throw new \Exception('Missing image');
        }

        if (!$this->size || $this->size < 16) {
            throw new \Exception('Invalid size');
        }
        $this->image = $image;
        $this->output = new Imagick();
        $this->output->setBackgroundColor(new ImagickPixel('transparent'));
        $this->output->readImageBlob($this->image);
        $this->imageFormat = $this->output->getImageFormat();

        return $this;
    }

    /**
     * Resizes an image to a custom size.
     *
     * @return Resize
     *
     * @throws \Exception
     */
    public function resize()
    {
        $width = $this->output->getImageWidth();
        $height = $this->output->getImageHeight();

        if (!$this->upscale && max($width, $height) < $this->size) {
            $this->output = $this->getImage();
        }

        $ratio = $width / $height;

        if ($ratio > 1) {
            $newWidth = $this->size;
            $newHeight = round($this->size / $ratio);
        } else {
            $newWidth = round($this->size * $ratio);
            $newHeight = $this->size;
        }

        $this->output->resizeImage($newWidth, $newHeight, Imagick::FILTER_CATROM, 1);

        return $this;
    }

    /**
     * Rounds the corners of an image
     * NOTE: We can't use Imagick::roundCorners due to https://github.com/Imagick/imagick/issues/213
     * @param int $x
     * @param int $y
     * @return self
     */
    public function roundCorners($x, $y): self
    {
        $width = $this->output->getImageWidth();
        $height = $this->output->getImageHeight();

        $mask = new \Imagick();
        $mask->newImage(
            $width,
            $height,
            new \ImagickPixel('transparent'),
            'png'
        );

        $shape = new \ImagickDraw();
        $shape->setFillColor(new \ImagickPixel('black'));
        $shape->roundRectangle(0, 0, $width -1, $height -1, $x, $y);

        $mask->drawImage($shape);

        $this->output->setImageMatte(1);
        $this->output->compositeImage($mask, \Imagick::COMPOSITE_DSTIN, 0, 0);

        return $this;
    }

    public function getImage()
    {
        if (!$this->output) {
            throw new \Exception('Output was not generated');
        }

        $this->output->setImageFormat($this->imageFormat);

        return $this->output->getImage();
    }
}
