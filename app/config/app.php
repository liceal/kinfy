<?php
/**
 * status 运行状态
 * providers 系统配置
 * providers_interface 系统配置接口
 *
 * providers 指向实例
 * 里面键指定 用于别的类  use 键名  如: use View 则会找到这里的View
 * 然后  use View => use \Kinfy\View\View
 * 但是别的地方不用 use \Kinfy\View\View
 *
 * provider_interface 指向每个实例的接口
 *
 */
return [
    'status' => 'RELEASE',//网站运行状态，RELEASE,DEBUG,SHUTDOWN
    'providers' => [
        'View' => \Kinfy\View\View::class,
    ],
    'provider_interface' => [
        'View' => \Kinfy\View\IView::class,
    ]

];