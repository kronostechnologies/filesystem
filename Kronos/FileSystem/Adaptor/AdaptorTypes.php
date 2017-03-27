<?php
/**
 * Created by PhpStorm.
 * User: mdemers
 * Date: 2017-03-27
 * Time: 11:08 AM
 */

namespace Kronos\FileSystem\Adaptor;


use Kronos\FileSystem\DTO\FileTypes;

class AdaptorTypes {

	const S3 = 'S3';
	const LOCAL = 'LOCAL';

	/**
	 * @param FileTypes|string $fileType
	 */
	public static function getDefaultForDocumentType($fileType){
		return self::LOCAL;
	}
}