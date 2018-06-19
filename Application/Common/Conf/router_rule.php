<?php

/**
 * 路由配置
 */
return array(
    'URL_ROUTE_RULES' => array(
        '/^unifiedorder(\/?)$/' => 'Home/WetCallback/wechatCall',
        '/^refund(\/?)$/' => 'Home/WetCallback/refund',
    ),
);
