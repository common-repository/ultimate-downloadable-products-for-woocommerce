<?php

/**
 * Original code from https://wordpress.org/plugins/image-blur/
 */

namespace UltimateDownloadableProducts\Service;

/**
 * Stop execution if not in Wordpress environment
 */
defined('WPINC') || die;

/**
 * Class for manipulating images.
 */
class ImageManipulation
{

	private $strength;
	private $blurWidth;

	function __construct($strength, $blurWidth)
	{
		$this->strength = $strength;
		$this->blurWidth = $blurWidth;
	}

	/**
	 * Processing function for images. We need mime type so we can process pngs with it's own method.
	 *
	 * @param string   $mime - mime type of the image object.
	 * @param \GdImage $image - Image object.
	 * @return \GdImage - modified Image object.
	 */
	public function process_image(string $mime, $image)
	{
		return $mime === 'image/png'
			? $this->process_png($image)
			: $this->generic_process($image);
	}

	/**
	 * Downscales passed in image while keeping aspect ratio to defined width and returns new downscaled image.
	 *
	 * @param \GdImage $image - Image object.
	 * @return \GdImage - Downscaled image object.
	 */
	public function downscale($image)
	{
		if (is_null($this->blurWidth)) {
			return $image;
		}
		$blurWidth = $this->blurWidth;
		$width = imagesx($image);
		$height = imagesy($image);
		if ($width > $height) {
			$blurWidth = intval(ceil($blurWidth * $width / $height));
		}
		return imagescale($image, $blurWidth);
	}

	/**
	 * Applies gaussian blur to passed in image.
	 * Blur's strength is applied using same function over and over again to the image object.
	 *
	 * @param \GdImage $image - Image object.
	 */
	public function gaussian_blur($image): void
	{
		// @see https://stackoverflow.com/a/7245782/4256005
		$gaussian = [
			[1, 2, 1],
			[2, 4, 2],
			[1, 2, 1]
		];
		// $divisor = array_sum(array_map('array_sum', $gaussian)); // 16
		$divisor = 16;
		for ($i = 1; $i <= $this->strength; $i++) {
			// imagefilter( $image, IMG_FILTER_GAUSSIAN_BLUR );
			imageconvolution($image, $gaussian, $divisor, 0);
		}
	}

	/**
	 * A generic process function for images.
	 *
	 * @param \GdImage $image - image object that needs processing.
	 * @return \GdImage $downscaled - downscaled and blurred image.
	 */
	public function generic_process($image)
	{
		$downscaled = $this->downscale($image);
		$this->gaussian_blur($downscaled);
		return $downscaled;
	}

	/**
	 * To keep transparency in png images, we need to process them using this function.
	 *
	 * @param \GdImage $image - Image object.
	 * @return \GdImage - modified Image object.
	 */
	public function process_png($image)
	{
		$width = imagesx($image);
		$height = imagesy($image);

		// create empty copy of passed in image using true color.
		/**
		 * @var \GdImage
		 */
		$new_image = imagecreatetruecolor($width, $height);

		// downscale and apply needed alpha and blending.
		$new_image = $this->downscale($new_image);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);

		$ds_width = imagesx($new_image);
		$ds_height = imagesy($new_image);

		// fill copy with transparent rectangle.
		$transparency = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
		imagefilledrectangle($new_image, 0, 0, $ds_width, $ds_height, $transparency);

		// paste image inside the copy.
		imagecopyresampled($new_image, $image, 0, 0, 0, 0, $ds_width, $ds_height, $width, $height);

		$this->gaussian_blur($new_image);

		return $new_image;
	}
}
