<?php
use Minifw\Console\Option;

return [
    'oppositePrefix' => 'no-',
    'comment' => [
        'usage: videocmp [action] [options] file|dir ...',
        '视频对比工具，可以分析视频的相似度，查找相似的视频',
    ],
    'options' => [
        'distance' => [
            'alias' => 'd',
            'comment' => '相似度判定参数，0-5，数字越大容忍度越高',
            'defalut' => 2,
            'paramType' => Option::PARAM_INT,
        ],
        'database' => [
            'alias' => ['db'],
            'comment' => '数据库文件的位置',
            'default' => '',
            'paramType' => Option::PARAM_FILE
        ],
        'config' => [
            'alias' => ['c'],
            'comment' => '配置文件的路径，默认为用户主目录下的 `.videocmp/config.json`',
            'default' => '',
            'paramType' => Option::PARAM_FILE
        ],
    ],
    'actions' => [
        'scan' => [
            'comment' => ['分析指定的目录或文件，在数据库中查找相似的视频，可以同时将该视频的信息记录进数据库'],
            'options' => [
                'save' => [
                    'alias' => 's',
                    'comment' => '在查找的同时把本视频的信息也添加进数据库中',
                    'defalut' => false,
                    'paramType' => Option::PARAM_BOOL,
                ],
                'distance',
                'config',
                'database',
            ]
        ],
        'findimg' => [
            'comment' => ['查找指定图片是否出现在数据库中的视频内'],
            'options' => [
                'distance',
                'config',
                'database',
            ]
        ],
        'config' => [
            'comment' => ['修改或查询程序的各项配置'],
            'options' => [
                'config',
                'get' => [
                    'alias' => 'g',
                    'comment' => '查询配置项',
                    'default' => [],
                    'paramType' => Option::PARAM_STRING,
                ],
                'set' => [
                    'alias' => 's',
                    'comment' => '修改配置项',
                    'default' => [],
                    'paramType' => [Option::PARAM_STRING, Option::PARAM_STRING],
                ],
            ]
        ],
        'help' => [
            'comment' => '显示本信息',
        ],
    ]
];
