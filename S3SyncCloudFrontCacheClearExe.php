<?php
class S3SyncCloudFrontCacheClearExe
{


    public static function exe($distribute, $dir, $bucket)
    {
        if (is_admin() && is_user_logged_in()) {

            try {

                exec('aws s3 sync  '.$dir.'  s3://'.$bucket.' --exact-timestamps', $output, $result_code);
                exec('aws cloudfront create-invalidation --distribution-id '.$distribute.' --paths "/*"', $output, $result_code);
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
            }
            // 画面にメッセージを表示
            if(isset($errorMsg)){
                $message_html = '<div class="notice notice-success is-dismissible">';
                $message_html .='<p>失敗しました</p>';
                $message_html .='<p>'.esc_html($errorMsg).'</p>';
                $message_html .='</div>';
            } else {
                $message_html =<<<EOF
<div class="notice notice-success is-dismissible">
  <p>
          処理しました！
  </p>
</div>
EOF;

            }
            echo wp_kses_post($message_html);
        }

	}
}

           

