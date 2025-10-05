<?php
return [
	'customer' => [
		'idcard'     => [
			'name' => '卡号'
		],
		'name'       => [
			'name' => '姓名'
		],
		'sex'        => [
			'name' => '性别'
		],
		'birthday'   => [
			'name' => '生日'
		],
		'age'        => [
			'name' => '年龄'
		],
		'phone'      => [
			'name' => '电话'
		],
		'address_id' => [
			'name' => '地址',
			'function' => 'getAddressName'
		],
		'medium_id'  => [
			'name' => '媒介',
			'function' => 'getMediumName'
		],
		'department_id' => [
			'name' => '科室',
			'function' => 'getDepartmentName'
		],
		'first_time' => [
			'name' => '初诊日期'
		],
		'last_time' => [
			'name' => '最近光临'
		],
		'ascription'    => [
			'name' => '开发人员',
			'function' => 'formatterUser'
		],
		'consultant'    => [
			'name' => '现场咨询',
			'function' => 'formatterUser'
		],
		'service'       => [
			'name' => '专属客服',
			'function' => 'formatterUser'
		],
		'tags' => [
			'name'     => '顾客标签',
			'function' => 'getTagsName'
		]
	],
	'reservation' => [
		'status'   => [
			'name' => '状态'
		],
		'type'     => [
			'name' => '受理类型',
			'function' => 'getReservationType'
		],
		'items'    => [
			'name' => '预约项目',
			'function' => 'getItemsName'
		],
		'time' => [
			'name' => '预约时间'
		],
		'medium_id' => [
			'name'     => '媒介来源',
			'function' => 'getMediumName'
		],
		'remark' => [
			'name' => '备注'
		]
	],
	'reception' => [
		'department_id' => [
			'name'     => '分诊科室',
			'function' => 'getDepartmentName'
		],
		'items' => [
			'name'     => '咨询项目',
			'function' => 'getItemsName'
		],
		'type' => [
			'name'     => '接诊状态',
			'function' => 'getReceptionType'
		],
		'status' => [
			'name'     => '成交状态',
			'function' => 'getReceptionStatus'
		],
		'consultant' => [
			'name'     => '现场咨询',
			'function' => 'formatterUser'
		],
		'reception' => [
			'name'     => '接待人员',
			'function' => 'formatterUser'
		],
		'doctor' => [
			'name'     => '助诊医生',
			'function' => 'formatterUser'
		],
		'remark' => [
			'name'     => '备注'
		]
	]
];