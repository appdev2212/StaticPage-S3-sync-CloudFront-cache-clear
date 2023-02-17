<?php
/*
  Plugin Name: StaticPage S3 sync and CloudFront cache clear
  Plugin URI:
  Description: 静的ファイルをS3へ同期してCloudFrontのキャッシュを削除する
  Version: 1.0.0
  Author: Yamaimo
  Author URI:https://it.kensan.net/
  License: GPLv2
 */

require_once('S3SyncCloudFrontCacheClearExe.php');
add_action('init', 'StaticPageS3SyncCloudFrontCacheClear::init');
class StaticPageS3SyncCloudFrontCacheClear
{


    const VERSION           = '1.0.0';
    const PLUGIN_ID         = 'staticpage-s3-sync-cloudfront-cacheclear';
    const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
    const CREDENTIAL_NAME   = self::PLUGIN_ID . '-nonce-key';
    const PLUGIN_DB_PREFIX  = self::PLUGIN_ID . '_';
    const CONFIG_MENU_SLUG  = self::PLUGIN_ID . '-config';
    const COMPLETE_CONFIG  = 'update-date-complete';


    static function init()
    {
        return new self();
    }

    function __construct()
    {
        if (is_admin() && is_user_logged_in()) {
            // メニュー追加
            add_action('admin_menu', [$this, 'set_plugin_menu']);

      	    // コールバック関数定義
	          add_action('admin_init', [$this, 'save_config']);

	      }
    }

    function set_plugin_menu()
    {
        add_menu_page(
            'StaticPage S3 sync and CloudFront cache clear',           /* ページタイトル*/
            'StaticPage S3 sync and CloudFront cache clear',           /* メニュータイトル */
            'manage_options',         /* 権限 */
            'custom-index-banner',    /* ページを開いたときのURL */
            [$this, 'show_config_form'],       /* メニューに紐づく画面を描画するcallback関数 */
            99                          /* 表示位置のオフセット */
        );
    }

    /** 設定画面の表示 */
    function show_config_form() {
      // ① wp_optionsのデータをひっぱってくる
      $distribute = get_option(self::PLUGIN_DB_PREFIX . 'distribute');
      $bucket = get_option(self::PLUGIN_DB_PREFIX . 'bucket');
      $dir = get_option(self::PLUGIN_DB_PREFIX . 'dir');
?>

      <div class="wrap">
        <h1>StaticPage S3 sync and CloudFront cache clear</h1>

        <form action="" method='post' id="my-submenu-form">
            <?php wp_nonce_field(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME) ?>

            <p>
              <label for="title">S3バケット名：</label>
              <input type="text" name="bucket" value="<?php echo esc_html($bucket) ?>"/>
            </p>

            <p>
              <label for="title">静的ファイルディレクトリ：</label>
              <input type="text" name="dir" value="<?php echo esc_html($dir) ?>"/>
            </p>

            <p>
              <label for="title">ディストリビューションID：</label>
              <input type="text" name="distribute" value="<?php echo esc_html($distribute) ?>"/>
            </p>

            <p><input type='submit' value='保存' class='button button-primary button-large' name='save'></p>
            <p><input type='submit' value='S3同期 & キャッシュクリア' class='button button-primary button-large' name='clear'></p>
        </form>
      </div>
<?php
    }

    /** 設定画面の項目データベースに保存する */
    function save_config()
    {

    	  // nonceで設定したcredentialのチェック 
        if (isset($_POST[self::CREDENTIAL_NAME]) && $_POST[self::CREDENTIAL_NAME]) {
          if (check_admin_referer(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME)) {
             if (isset($_POST['save'])) {
            	  try {
	                // 保存処理
        	        $key   = 'distribute';
                	$distribute = wp_kses_post($_POST['distribute']);
                	update_option(self::PLUGIN_DB_PREFIX . $key, $distribute);      
        	        $key   = 'bucket';
                	$bucket = wp_kses_post($_POST['bucket']);
                	update_option(self::PLUGIN_DB_PREFIX . $key, $bucket);
        	        $key   = 'dir';
                	$dir = wp_kses_post($_POST['dir']);
                	update_option(self::PLUGIN_DB_PREFIX . $key, $dir);
              
    			        // 画面にメッセージを表示
			            $message_html =<<<EOF
<div class="notice notice-success is-dismissible">
	<p>
  保存しました
	</p>
</div>
EOF;
		            } catch (Exception $ex) {
                  $errorMsg =  $ex->getMessage ();
                  $message_html = '<div class="notice notice-success is-dismissible">';
                  $message_html .='<p>保存に失敗しました</p>';
                  $message_html .='<p>'.esc_html($errorMsg).'</p>';
                  $message_html .='</div>';
		            }
              echo wp_kses_post($message_html);
      	      } elseif(isset($_POST['clear'])) {
                  $distribute = get_option(self::PLUGIN_DB_PREFIX . 'distribute');
                  $bucket = get_option(self::PLUGIN_DB_PREFIX . 'bucket');
                  $dir = get_option(self::PLUGIN_DB_PREFIX . 'dir');
                  S3SyncCloudFrontCacheClearExe::exe($distribute, $dir, $bucket);
	            }
          }
      }
   }
} // end of class



