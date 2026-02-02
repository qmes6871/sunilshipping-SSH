<?php
/**
 * Facebook Page Plugin Module (Mobile Responsive)
 */

/**
 * Display Facebook page plugin with CSS scaling
 */
function display_facebook_plugin(
    $page_url = 'https://www.facebook.com/profile.php?id=61552812922686',
    $width = 500,
    $height = 900,
    $small_header = false,
    $adapt_container_width = true,
    $hide_cover = false,
    $show_facepile = true,
    $tabs = 'timeline',
    $app_id = '1792332464641194',
    $scale = 1
) {
    // 최대값 제한
    $actual_width = min($width, 500);
    $mobile_width = min($width, 340); // 모바일용 너비

    // Build the iframe source URL (desktop)
    $iframe_src = "https://www.facebook.com/plugins/page.php?" . http_build_query([
        'href' => $page_url,
        'tabs' => $tabs,
        'width' => $actual_width,
        'height' => $height,
        'small_header' => $small_header ? 'true' : 'false',
        'adapt_container_width' => $adapt_container_width ? 'true' : 'false',
        'hide_cover' => $hide_cover ? 'true' : 'false',
        'show_facepile' => $show_facepile ? 'true' : 'false',
        'appId' => $app_id
    ]);

    // Build the iframe source URL (mobile)
    $iframe_src_mobile = "https://www.facebook.com/plugins/page.php?" . http_build_query([
        'href' => $page_url,
        'tabs' => $tabs,
        'width' => $mobile_width,
        'height' => $height,
        'small_header' => true, // 모바일에서는 작은 헤더 사용
        'adapt_container_width' => true,
        'hide_cover' => false,
        'show_facepile' => true,
        'appId' => $app_id
    ]);

    // 확대된 크기 계산
    $scaled_width = $actual_width * $scale;
    $scaled_height = $height * $scale;

    // CSS for scaling with mobile responsive
    $css = sprintf('<style>
        .fb-page-wrapper {
            width: 100%%;
            max-width: %dpx;
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid #e4e6eb;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }
        .fb-page-wrapper iframe {
            display: block;
            border: none;
            overflow: hidden;
        }

        /* 데스크톱 */
        @media (min-width: 769px) {
            .fb-page-wrapper {
                height: %dpx;
            }
            .fb-page-wrapper iframe {
                transform: scale(%s);
                transform-origin: 0 0;
                width: %dpx !important;
                height: %dpx !important;
            }
        }

        /* 태블릿 */
        @media (max-width: 768px) and (min-width: 481px) {
            .fb-page-wrapper {
                max-width: 100%%;
                padding: 0 10px;
                border: none;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .fb-page-wrapper iframe {
                width: 100%% !important;
                height: 700px !important;
                transform: none;
            }
        }

        /* 모바일 */
        @media (max-width: 480px) {
            .fb-page-wrapper {
                max-width: 100%%;
                margin: 0;
                padding: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }
            .fb-page-wrapper iframe.desktop-fb {
                display: none;
            }
            .fb-page-wrapper iframe.mobile-fb {
                display: block;
                width: 100%% !important;
                height: 600px !important;
                transform: none;
            }
        }

        /* 작은 모바일 */
        @media (max-width: 360px) {
            .fb-page-wrapper iframe.mobile-fb {
                height: 500px !important;
            }
        }

        /* 데스크톱에서 모바일 iframe 숨기기 */
        @media (min-width: 481px) {
            .fb-page-wrapper iframe.mobile-fb {
                display: none;
            }
        }
    </style>',
        $scaled_width,
        $scaled_height,
        $scale,
        $actual_width,
        $height
    );

    // Build iframe with wrapper (데스크톱 + 모바일 두 개)
    $iframe_html = sprintf(
        '%s<div class="fb-page-wrapper">
            <iframe class="desktop-fb" src="%s" width="%d" height="%d" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
            <iframe class="mobile-fb" src="%s" width="%d" height="%d" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
        </div>',
        $css,
        htmlspecialchars($iframe_src, ENT_QUOTES, 'UTF-8'),
        $actual_width,
        $height,
        htmlspecialchars($iframe_src_mobile, ENT_QUOTES, 'UTF-8'),
        $mobile_width,
        $height
    );

    return $iframe_html;
}

/**
 * Print Facebook page plugin directly
 */
function print_facebook_plugin($options = []) {
    $defaults = [
        'page_url' => 'https://www.facebook.com/profile.php?id=61552812922686',
        'width' => 500,
        'height' => 900,
        'small_header' => false,
        'adapt_container_width' => true,
        'hide_cover' => false,
        'show_facepile' => true,
        'tabs' => 'timeline',
        'app_id' => '1792332464641194',
        'scale' => 1
    ];

    $config = array_merge($defaults, $options);

    echo display_facebook_plugin(
        $config['page_url'],
        $config['width'],
        $config['height'],
        $config['small_header'],
        $config['adapt_container_width'],
        $config['hide_cover'],
        $config['show_facepile'],
        $config['tabs'],
        $config['app_id'],
        $config['scale']
    );
}
?>
