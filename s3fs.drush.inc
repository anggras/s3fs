<?php

/**
 * @file
 * Defines a drush command that refreshes the S3 metadata cache.
 */

use Drupal\s3fs\Plugin\Handler\S3fsHandler;
use Symfony\Component\Yaml\Yaml;

/**
* Implements hook_drush_command().
*/
function s3fs_drush_command() {
	$file = preg_replace('/(inc|php)$/', 'yml', __FILE__);
	$config = Yaml::parse(file_get_contents($file));
	$items = $config['commands'];
	return $items;
}

/**
 * Copys all files from the local public/private filesystem folders into S3,
 * if s3fs is configured to take over those systems.
 */
function drush_s3fs_copy_local() {
	$s3 = new S3fsHandler();
	$config = $s3->get('config');

	// Need to populate array with options from settings.
	// $config->get('use_s3_for_public').
	$filesystems = ['public'];

	if (empty($filesystems)) {
		drupal_set_message(
			t('S3 File System is not configured to take over any other file systems.')
		);
		return;
	}
	else {
		foreach ($filesystems as $type) {
			$s3->copyFsToS3($type);
		}
	}
}
