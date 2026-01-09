<?php

namespace App\Support;

class CoATemplates
{
    /**
     * Basic Japanese-style P/L chart of accounts.
     * Adjust as needed; codes are materialized into project_accounts.
     */
    public static function jpPL(): array
    {
        return [
            [
                'code' => '4000',
                'name' => '[売上高]',
                'is_postable' => false,
                'children' => [
                    ['code' => '4010', 'name' => '売上高', 'is_postable' => true],
                    ['code' => '4020', 'name' => '売上高合計', 'is_postable' => false, 'is_formula' => true, 'formula' => '[4010]'],
                ],
            ],
            [
                'code' => '5000',
                'name' => '[売上原価]',
                'is_postable' => false,
                'children' => [
                    ['code' => '5010', 'name' => '期首商品棚卸高', 'is_postable' => true],
                    ['code' => '5020', 'name' => '当期商品仕入高', 'is_postable' => true],
                    ['code' => '5030', 'name' => '期末商品棚卸高', 'is_postable' => true],
                    ['code' => '5040', 'name' => '売上原価', 'is_postable' => false, 'is_formula' => true, 'formula' => '[5010]+[5020]-[5030]'],
                    ['code' => '5050', 'name' => '売上総損益金額', 'is_postable' => false, 'is_formula' => true, 'formula' => '[4010]-[5040]'],
                ],
            ],
            [
                'code' => '6000',
                'name' => '[販売管理費]',
                'is_postable' => false,
                'children' => [
                    ['code' => '6010', 'name' => '役員報酬', 'is_postable' => true],
                    ['code' => '6020', 'name' => '役員賞与', 'is_postable' => true],
                    ['code' => '6030', 'name' => '給料手当', 'is_postable' => true],
                    ['code' => '6035', 'name' => '賞与', 'is_postable' => true],
                    ['code' => '6040', 'name' => '法定福利費', 'is_postable' => true],
                    ['code' => '6050', 'name' => '福利厚生費', 'is_postable' => true],
                    ['code' => '6060', 'name' => '外注費', 'is_postable' => true],
                    ['code' => '6070', 'name' => '荷造運賃', 'is_postable' => true],
                    ['code' => '6080', 'name' => '広告宣伝費', 'is_postable' => true],
                    ['code' => '6090', 'name' => '交際費', 'is_postable' => true],
                    ['code' => '6100', 'name' => '会議費', 'is_postable' => true],
                    ['code' => '6110', 'name' => '旅費交通費', 'is_postable' => true],
                    ['code' => '6120', 'name' => '通信費', 'is_postable' => true],
                    ['code' => '6130', 'name' => '消耗品費', 'is_postable' => true],
                    ['code' => '6140', 'name' => '水道光熱費', 'is_postable' => true],
                    ['code' => '6150', 'name' => '諸会費', 'is_postable' => true],
                    ['code' => '6155', 'name' => '支払手数料', 'is_postable' => true],
                    ['code' => '6160', 'name' => '車両費', 'is_postable' => true],
                    ['code' => '6170', 'name' => '地代家賃', 'is_postable' => true],
                    ['code' => '6180', 'name' => '賃借料', 'is_postable' => true],
                    ['code' => '6190', 'name' => 'リース料', 'is_postable' => true],
                    ['code' => '6200', 'name' => '保険料', 'is_postable' => true],
                    ['code' => '6210', 'name' => '租税公課', 'is_postable' => true],
                    ['code' => '6220', 'name' => '支払報酬料', 'is_postable' => true],
                    ['code' => '6230', 'name' => '寄付金', 'is_postable' => true],
                    ['code' => '6240', 'name' => '減価償却費', 'is_postable' => true],
                    ['code' => '6250', 'name' => '研修費', 'is_postable' => true],
                    ['code' => '6260', 'name' => '間接費配賦', 'is_postable' => false, 'is_formula' => true, 'formula' => '([6010]+[6020]+[6030]+[6035]+[6040]+[6050]+[6060]+[6070]+[6080]+[6090]+[6100]+[6110]+[6120]+[6130]+[6140]+[6150]+[6155]+[6160]+[6170]+[6180]+[6190]+[6200]+[6210]+[6220]+[6230]+[6240]+[6250])*0.1'],
                    ['code' => '6270', 'name' => '販売管理費計', 'is_postable' => false, 'is_formula' => true, 'formula' => '[6010]+[6020]+[6030]+[6035]+[6040]+[6050]+[6060]+[6070]+[6080]+[6090]+[6100]+[6110]+[6120]+[6130]+[6140]+[6150]+[6155]+[6160]+[6170]+[6180]+[6190]+[6200]+[6210]+[6220]+[6230]+[6240]+[6250]+[6260]'],
                    // ['code' => '6280', 'name' => '営業損益金額', 'is_postable' => false, 'is_formula' => true, 'formula' => '[5050]-[6270]'],
                    
                ],
            ],
            // [
            //     'code' => '7000',
            //     'name' => '[営業外収益]',
            //     'is_postable' => false,
            //     'children' => [
            //         ['code' => '7010', 'name' => '受取利息', 'is_postable' => true],
            //         ['code' => '7020', 'name' => '受取配当金', 'is_postable' => true],
            //         ['code' => '7030', 'name' => '雑収入', 'is_postable' => true],
            //         ['code' => '7040', 'name' => '営業外収益合計', 'is_postable' => false, 'is_formula' => true, 'formula' => '[7010]+[7020]+[7030]'],
            //     ],
            // ],
            // [
            //     'code' => '7100',
            //     'name' => '[営業外費用]',
            //     'is_postable' => false,
            //     'children' => [
            //         ['code' => '7110', 'name' => '支払利息', 'is_postable' => true],
            //         ['code' => '7120', 'name' => '営業外費用合計', 'is_postable' => false, 'is_formula' => true, 'formula' => '[7110]'],
            //         ['code' => '7130', 'name' => '経常損益金額', 'is_postable' => false, 'is_formula' => true, 'formula' => '[6270]+[7040]-[7120]'],
            //     ],
            // ],
            // [
            //     'code' => '8000',
            //     'name' => '[特別利益]',
            //     'is_postable' => false,
            //     'children' => [
            //         ['code' => '8010', 'name' => '特別利益合計', 'is_postable' => true],
            //     ],
            // ],
            // [
            //     'code' => '8100',
            //     'name' => '[特別損失]',
            //     'is_postable' => false,
            //     'children' => [
            //         ['code' => '8110', 'name' => '特別損失合計', 'is_postable' => true],
            //     ],
            // ],
            // [
            //     'code' => '9000',
            //     'name' => '[当期純損益]',
            //     'is_postable' => false,
            //     'children' => [
            //         ['code' => '9010', 'name' => '税引前当期純損益金額', 'is_postable' => false, 'is_formula' => true, 'formula' => '[7130]+[8010]-[8110]'],
            //         ['code' => '9020', 'name' => '法人税、住民税及び事業税', 'is_postable' => true],
            //         ['code' => '9030', 'name' => '当期純損益金額', 'is_postable' => false, 'is_formula' => true, 'formula' => '[9010]-[9020]'],
            //     ],
            // ],
            [
                'code' => '9100',
                'name' => '利益',
                'is_postable' => false,
                'children' => [
                    ['code' => '9110', 'name' => '通常利益', 'is_postable' => false, 'is_formula' => true, 'formula' => '[5050]-[6270]'],
                    ['code' => '9120', 'name' => '業績連動賞与積立金', 'is_postable' => false, 'is_formula' => true, 'formula' => '[9110]*0.2'],
                    ['code' => '9130', 'name' => '利益', 'is_postable' => false, 'is_formula' => true, 'formula' => '[9110]-[9120]'],
                ]
            ]
        ];
    }
}
