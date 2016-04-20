<?php

/**
 * @file
 * Contains \Drupal\s3fs\Form\SettingsForm.
 */

namespace Drupal\s3fs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {
	/**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\user\AccountSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The configuration context.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

	/**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 's3fs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['s3fs.settings'];
  }

	/**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'new') {
		$config = $this->configFactory->get('s3fs.settings');

    // I'd like to be able to pull this information directly from the SDK, but
    // I couldn't find a good way to get the human-readable region names.
    $region_map = [
      '' => 'Default',
      'us-east-1' => 'US Standard (us-east-1)',
      'us-west-1' => 'US West - Northern California  (us-west-1)',
      'us-west-2' => 'US West - Oregon (us-west-2)',
      'us-gov-west-1' => 'USA GovCloud Standard (us-gov-west-1)',
      'eu-west-1' => 'EU - Ireland  (eu-west-1)',
      'eu-central-1' => 'EU - Frankfurt (eu-central-1)',
      'ap-southeast-1' => 'Asia Pacific - Singapore (ap-southeast-1)',
      'ap-southeast-2' => 'Asia Pacific - Sydney (ap-southeast-2)',
      'ap-northeast-1' => 'Asia Pacific - Tokyo (ap-northeast-1)',
      'sa-east-1' => 'South America - Sao Paulo (sa-east-1)',
      'cn-north-1' => 'China - Beijing (cn-north-1)',
    ];
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => t('Amazon Web Services Credentials'),
      '#description' => t(
        "To configure your Amazon Web Services credentials, enter the values in the appropriate fields below.
        You may instead set \$conf['awssdk2_access_key'] and \$conf['awssdk2_secret_key'] in your site's settings.php   file.
        Values set in settings.php will override the values in these fields."
      ),
      '#collapsible' => TRUE,
      '#collapsed' => $config->get('use_instance_profile'),
    ];

    $form['credentials']['access_key'] = [
      '#type' => 'textfield',
      '#title' => t('Amazon Web Services Access Key'),
      '#default_value' => $config->get('access_key'),
    ];

    $form['credentials']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Amazon Web Services Secret Key'),
      '#default_value' =>  $config->get('secret_key'),
    ];

    $form['credentials']['use_instance_profile'] = [
      '#type' => 'checkbox',
      '#title' => t('Use EC2 Instance Profile Credentials'),
      '#default_value' =>  $config->get('use_instance_profile'),
      '#description' => t(
        'If your Drupal site is running on an Amazon EC2 server, you may use the Instance Profile Credentials from that server
        rather than setting your AWS credentials directly.'
      ),
    ];
  $form['credentials']['default_cache_config'] = [
    '#type' => 'textfield',
    '#title' => t('Default Cache Location'),
    '#default_value' => $config->get('default_cache_config'),
    '#description' => t('The default cache location for your EC2 Instance Profile Credentials.'),
    '#states' => [
      'visible' => [
        ':input[id=edit-use-instance-profile]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['bucket'] = [
    '#type'           => 'textfield',
    '#title'          => t('S3 Bucket Name'),
    '#default_value'  => $config->get('bucket'),
    '#required'       => TRUE,
  ];

  $form['region'] = [
    '#type'          => 'select',
    '#options'       => $region_map,
    '#title'         => t('S3 Region'),
    '#default_value' => $config->get('region'),
    '#description'   => t(
      'The region in which your bucket resides. Be careful to specify this accurately,
      as you are likely to see strange or broken behavior if the region is set wrong.<br>
      Use of the USA GovCloud region requires @SPECIAL_PERMISSION.<br>
      Use of the China - Beijing region requires a @CHINESE_AWS_ACCT.',
      [
          '@CHINESE_AWS_ACCT' => \Drupal::l('亚马逊 AWS account', Url::fromUri('http://www.amazonaws.cn')),
          '@SPECIAL_PERMISSION' => \Drupal::l('special permission', Url::fromUri('http://aws.amazon.com/govcloud-us/')),
      ]
    ),
  ];

  $form['advanced'] = [
    '#type' => 'fieldset',
    '#title' => t('Advanced Configuration Options'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $advanced = &$form['advanced'];
  $advanced['use_cname'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable CNAME'),
    '#default_value' => $config->get('use_cname'),
    '#description'   => t('Serve files from a custom domain by using an appropriately named bucket, e.g. "mybucket.mydomain.com".'),
  ];
  $advanced['cname_settings_fieldset'] = [
    '#type' => 'fieldset',
    '#title' => t('CNAME Settings'),
    '#states' => [
      'visible' => [
        ':input[id=edit-use-cname]' => ['checked' => TRUE],
      ],
    ],
  ];
  $advanced['use_customhost'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Use a Custom Host'),
    '#default_value' => $config->get('use_customhost'),
    '#description'   => t('Connect to an S3-compatible storage service other than Amazon.'),
  ];
  $advanced['customhost_settings_fieldset'] = [
    '#type' => 'fieldset',
    '#title' => t('Custom Host Settings'),
    '#states' => [
      'visible' => [
        ':input[id=edit-use-customhost]' => ['checked' => TRUE],
      ],
    ],
  ];
  $advanced['customhost_settings_fieldset']['hostname'] = [
    '#type'          => 'textfield',
    '#title'         => t('Hostname'),
    '#default_value' => $config->get('hostname'),
    '#description'   => t('Custom service hostname, e.g. "objects.dreamhost.com".'),
    '#states'        => [
      'visible' => [
        ':input[id=edit-s3fs-use-customhost]' => ['checked' => TRUE],
      ],
    ],
  ];
  $advanced['cname_settings_fieldset']['domain'] = [
    '#type'          => 'textfield',
    '#title'         => t('CDN Domain Name'),
    '#default_value' => $config->get('domain'),
    '#description'   => t('If serving files from CloudFront, the bucket name can differ from the domain name.'),
  ];
  $advanced['cache_control_header'] = [
    '#type'          => 'textfield',
    '#title'         => t('S3 Object Cache-Control Header'),
    '#default_value' => $config->get('cache_control_header'),
    '#description'   => t('The cache control header to set on all S3 objects for CDNs and browsers, e.g.
      "public, max-age=300".'
    ),
  ];
  $advanced['encryption'] = [
    '#type'          => 'select',
    '#options'       => ['' => 'None', 'AES256' => 'AES256', 'aws:kms' => 'aws:kms'],
    '#title'         => t('Server-Side Encryption'),
    '#default_value' => $config->get('encryption'),
    '#description'   => t(
      'If your bucket requires @ENCRYPTION, you can specify the encryption algorithm here',
      [
        '@ENCRYPTION' => \Drupal::l('server-side encryption',
          Url::fromUri('http://docs.aws.amazon.com/AmazonS3/latest/dev/UsingServerSideEncryption.html'
        )),
      ]
    ),
  ];

  $advanced['use_https'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Always serve files from S3 via HTTPS'),
    '#default_value' => $config->get('use_https'),
    '#description'   => t(
      'Forces S3 File System to always generate HTTPS URLs for files in your bucket,
      e.g. "https://mybucket.s3.amazonaws.com/smiley.jpg".<br>
      Without this setting enabled, URLs for your files will use the same scheme as the page they are served from.'
    ),
  ];
  $advanced['ignore_cache'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Ignore the file metadata cache'),
    '#default_value' => $config->get('ignore_cache'),
    '#description'   => t(
      "If you need to debug a problem with S3, you may want to temporarily ignore the file metadata cache.
      This will make all file system reads hit S3 instead of the cache.<br>
      <b>This causes s3fs to work extremely slowly, and should never be enabled on a production site.</b>"
    ),
  ];
  $advanced['use_s3_for_public'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Use S3 for public:// files'),
    '#default_value' => $config->get('use_s3_for_public'),
    '#description'   => t(
      'Enable this option to store all files which would be uploaded to or created in the web server\'s local file system
      within your S3 bucket instead.<br><br>
      <b>PLEASE NOTE:</b> If you intend to use Drupal\'s performance options which aggregate your CSS or Javascript
      files, or will be using any other system that writes CSS or Javascript files into your site\'s public:// file system,
      you must perform some additional configuration on your webserver to make those files work correctly when stored in S3.
      Please see the section titled "Aggregated CSS and JS in S3" in the README for details.'
    ),
  ];
  $advanced['no_rewrite_cssjs'] = [
    '#type' => 'checkbox',
    '#title' => t("Don't rewrite CSS/JS file paths"),
    '#default_value' => $config->get('no_rewrite_cssjs'),
    '#description' => t(
      'If this box is checked, s3fs will NOT rewrite the CSS/JS file paths to "/s3fs-(css|js)/...". Instead, they will
      be placed on the page with their regular CDN name. Only enable this option if you <b>know</b> you need it!'
    ),
    '#states'        => [
      'visible' => [
        ':input[id=edit-use-s3-for-public]' => ['checked' => TRUE],
      ],
    ],
  ];
  $advanced['use_s3_for_private'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Use S3 for private:// files'),
    '#default_value' => $config->get('use_s3_for_private'),
    '#description'   => t(
      'Enable this option to store all files which would be uploaded to or created in the private://
      file system (files available only to authneticated users) within your S3 bucket instead.'
    ),
  ];
  $advanced['root_folder'] = [
    '#type'           => 'textfield',
    '#title'          => t('Root Folder'),
    '#default_value'  =>  $config->get('root_folder'),
    '#description'   => t(
      'S3 File System uses the specified folder as the root of the file system within your bucket (if blank, the bucket
      root is used). This is helpful when your bucket is used by multiple sites, or has additional data in it which
      s3fs should not interfere with.<br>
      The metadata refresh function will not retrieve metadata for any files which are outside the Root Folder.<br>
      This setting is case sensitive. Do not include leading or trailing slashes.<br>
      Changing this setting <b>will not</b> move any files. If you\'ve already uploaded files to S3 through S3 File
      System, you will need to manually move them into this folder.'
    ),
  ];
  $advanced['file_specific'] = [
    '#type' => 'fieldset',
    '#title' => t('File-specific Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $file_specific = &$advanced['file_specific'];
  $file_specific['presigned_urls'] = [
    '#type' => 'textarea',
    '#title' => t('Presigned URLs'),
    '#default_value' => $config->get('presigned_urls'),
    '#rows' => 5,
    '#description' => t(
      'A list of timeouts and paths that should be delivered through a presigned url.<br>
      Enter one value per line, in the format timeout|path. e.g. "60|private_files/*". Paths use regex patterns
      as per @link. If no timeout is provided, it defaults to 60 seconds.<br>
      <b>This feature does not work when "Enable CNAME" is used.</b>',
      ['@link' => \Drupal::l('preg_match', Url::fromUri('http://php.net/preg_match'))]
    ),
  ];
  $file_specific['saveas'] = [
    '#type' => 'textarea',
    '#title' => t('Force Save As'),
    '#default_value' => $config->get('saveas'),
    '#rows' => 5,
    '#description' => t(
      'A list of paths for which users will be forced to save the file, rather than displaying it in the browser.<br>
      Enter one value per line. e.g. "video/*". Paths use regex patterns as per @link.<br>
      <b>This feature does not work when "Enable CNAME" is used.</b>',
      ['@link' => \Drupal::l('preg_match', Url::fromUri('http://php.net/preg_match'))]
    ),
  ];
  $file_specific['torrents'] = [
    '#type' => 'textarea',
    '#title' => t('Torrents'),
    '#default_value' => $config->get('torrents'),
    '#rows' => 5,
    '#description' => t(
      'A list of paths that should be delivered via BitTorrent.<br>
      Enter one value per line, e.g. "big_files/*". Paths use regex patterns as per @link.<br>
      <b>Private files and paths which are already set as Presigned URLs or Forced Save As cannot be delivered as torrents.</b>',
      ['@link' => \Drupal::l('preg_match', Url::fromUri('http://php.net/preg_match'))]
    ),
  ];

    return parent::buildForm($form, $form_state);
  }

	/**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

		$config = $this->config('s3fs.settings');

		$config
      ->set('access_key', $form_state->getValue('access_key'))
      ->set('secret_key', $form_state->getValue('secret_key'))
      ->set('use_instance_profile', $form_state->getValue('use_instance_profile'))
      ->set('default_cache_config', $form_state->getValue('default_cache_config'))
      ->set('bucket', $form_state->getValue('bucket'))
      ->set('region', $form_state->getValue('region'))
      ->set('use_cname', $form_state->getValue('use_cname'))
      ->set('use_customhost', $form_state->getValue('use_customhost'))
      ->set('hostname', $form_state->getValue('hostname'))
      ->set('domain', $form_state->getValue('domain'))
      ->set('cache_control_header', $form_state->getValue('cache_control_header'))
      ->set('encryption', $form_state->getValue('encryption'))
      ->set('use_https', $form_state->getValue('use_https'))
      ->set('ignore_cache', $form_state->getValue('ignore_cache'))
      ->set('use_s3_for_public', $form_state->getValue('use_s3_for_public'))
      ->set('no_rewrite_cssjs', $form_state->getValue('no_rewrite_cssjs'))
      ->set('use_s3_for_private', $form_state->getValue('use_s3_for_private'))
      ->set('root_folder', trim($form_state->getValue('root_folder'), '\/'))
      ->set('presigned_urls', $form_state->getValue('presigned_urls'))
      ->set('saveas', $form_state->getValue('saveas'))
      ->set('torrents', $form_state->getValue('torrents'))
      ->save();

    drupal_set_message(t("Your settings have been saved succesfully"));
  }


}
