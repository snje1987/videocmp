<?php
use Minifw\Console\Option;

return [
    'oppositePrefix' => 'no-',
    'comment' => [
        'usage: videocmp [action] [options] file|dir ...',
        '视频对比工具，可以分析视频的相似度，查找相似的视频',
    ],
    'template' => [
        'distance' => [
            'alias' => 'd',
            'comment' => '相似度判定参数, 0-3, 数字越大容忍度越高',
            'default' => 1,
            'type' => Option::PARAM_INT,
        ],
        'match' => [
            'alias' => 'm',
            'comment' => '1-1000, 当两个文件相似度达到该值时会写入到分析结果中',
            'default' => 50,
            'type' => Option::PARAM_INT,
        ],
    ],
    'global' => [
        'database' => [
            'alias' => ['db'],
            'comment' => '数据库文件的位置',
            'default' => '',
            'type' => Option::PARAM_PATH
        ],
        'config' => [
            'alias' => ['c'],
            'comment' => '配置文件的路径，默认为用户主目录下的 `.videocmp/config.json`',
            'default' => '',
            'type' => Option::PARAM_PATH
        ],
    ],
    'actions' => [
        'scan' => [
            'comment' => ['分析指定的目录或文件，在数据库中查找相似的视频，可以同时将该视频的信息记录进数据库'],
            'options' => [
                'out' => [
                    'alias' => 'o',
                    'comment' => '将查找的结果输出到文件',
                    'default' => null,
                    'type' => Option::PARAM_PATH,
                ],
                'save' => [
                    'alias' => 's',
                    'comment' => '在查找的同时把本视频的信息也添加进数据库中',
                    'default' => false,
                    'type' => Option::PARAM_BOOL,
                ],
                'override' => [
                    'comment' => '在添加视频信息时，如果发现被分析的文件在数据库中已经存在，则用新文件的信息替换数据库中的信息',
                    'default' => false,
                    'type' => Option::PARAM_BOOL,
                ],
                'rescan' => [
                    'comment' => '重新分析已经在数据库中的文件',
                    'default' => false,
                    'type' => Option::PARAM_BOOL,
                ],
                'rescan-override' => [
                    'comment' => '重新分析新替换到数据库中的文件',
                    'default' => false,
                    'type' => Option::PARAM_BOOL,
                ],
                'distance', 'match'
            ]
        ],
        'missing' => [
            'comment' => ['查找已经被从文件系统中删除的数据库中的文件'],
            'options' => [
                'save' => [
                    'alias' => 's',
                    'comment' => '删除数据库中对应的信息',
                    'default' => false,
                    'type' => Option::PARAM_BOOL,
                ],
            ]
        ],
        'config' => [
            'comment' => ['修改或查询程序的各项配置'],
            'options' => [
                'get' => [
                    'alias' => 'g',
                    'comment' => '查询配置项',
                    'default' => [],
                    'type' => Option::PARAM_STRING,
                ],
                'set' => [
                    'alias' => 's',
                    'comment' => '修改配置项',
                    'default' => [],
                    'type' => [Option::PARAM_STRING, Option::PARAM_STRING],
                ],
            ]
        ],
        'dump' => [
            'comment' => '导出视频帧信息',
            'options' => [
                'size' => [
                    'type' => Option::PARAM_INT,
                ],
            ],
        ],
        'help' => [
            'comment' => '显示本信息',
        ],
    ]
];
