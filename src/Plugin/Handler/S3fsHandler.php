<?php

/**
 * @file
 * Contains \Drupal\s3fs\Plugin\Handler\S3fsHandler.
 */

namespace Drupal\s3fs\Plugin\Handler;

use Aws\S3\S3Client;
use Aws\S3\Exception;

/**
 * Plugin implementation of the S3fsHandler.
 */
class S3fsHandler {

  private $config;
  private $s3;

  /**
   * Construct social links.
   *
   * @param array &$links[description]
   *   [description]
   * @param bool &$override
   *   [description]
   */
  public function __construct() {
		$this->setConfig();
		$this->setS3($this->config);

  }


	/**
	 * Sets up the S3Client object.
	 *
	 * For performance reasons, only one S3Client object will ever be created
	 * within a single request.
	 *
	 * @param $config Array
	 *   Array of configuration settings from which to configure the client.
	 *
	 * @return Aws\S3\S3Client
	 *   The fully-configured S3Client object.
	 */
	 /**
    * Add links.
    *
    * @param [type] $links
    *   [description]
    */
	public function setS3($config) {
		$client_config =	[
			'credentials' => [
				 'key'    => $config->get('access_key'),
				 'secret' => $config->get('secret_key'),
			],
				'region'  => $config->get('region'),
				'version' => 'latest'
		];

		$s3 = S3Client::factory($client_config);

		$this->set('s3', $s3);
	}



	/**
	 * Copies all the local files from the specified file system into S3.
	 */
	public function copyFsToS3($scheme) {
		$config = $this->get('config');
		$s3 = $this->get('s3');

		$filesystem = \Drupal::service('stream_wrapper_manager')->getViaUri('public://');
		$s3fs = \Drupal::service('stream_wrapper_manager')->getClass('s3fs');
		$target_folder = 's3fs-' . $scheme;

		$root_folder = $config->get('root_folder');
		if ($root_folder) {
			$target_folder = $root_folder . '/' . $target_folder;
		}

		$source_folder = $filesystem->realpath();

		$file_paths = $this->recursive_dir_scan($source_folder);

		foreach ($file_paths as $path) {
			$relative_path = str_replace($source_folder . '/', '', $path);
			print "Copying $scheme://$relative_path into S3...\n";
			copy($path, "s3fs://$relative_path");
		}

		drupal_set_message(t('Copied all local %scheme files to S3.', ['%scheme' => $scheme]), 'status');
	}




	public function recursive_dir_scan($dir) {
	  $output = array();
	  $files = scandir($dir);
	  foreach ($files as $file) {
	    $path = "$dir/$file";

	    if ($file != '.' && $file != '..') {
	      // In case they put their private root folder inside their public one,
	      // skip it. When listing the private file system contents, $path will
	      // never trigger this.


	      if (is_dir($path)) {
	        $output = array_merge($output, $this->recursive_dir_scan($path));
	      }
	      else {
	        $output[] = $path;
	      }
	    }
	  }
	  return $output;
	}






	/**
   * Add links.
   *
   * @param [type] $links
   *   [description]
   */
  public function setConfig() {
		$this->set('config', \Drupal::config('s3fs.settings'));
  }

	/**
   * Add links.
   *
   * @param [type] $links
   *   [description]
   */
  public function get($property) {
		return $this->{$property};
  }

  /**
   * Add links.
   *
   * @param [type] $links
   *   [description]
   */
  public function set($property, $value) {
		$this->{$property} = $value;
  }

}
