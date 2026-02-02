import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#goalSetting',
            popover: {
                title: '目標設定', 
                description: 'ここでプロジェクトの目標値（ゴール）を設定できます。あとから変更も可能です。', 
                side: 'top', 
                align: 'start',
                showButtons: ['next'],
            },
        },
        {
            element: '#unitSelection',
            popover: {
                title: '成果単位', 
                description: 'ここでプロジェクトの成果単位選択できます。カスタム成果単位も可能です。', 
                side: 'top', 
                align: 'start',
                showButtons: ['previous', 'next'],
            },
        },
        {
            element: '#achievementItems',
            popover: {
                title: '実績項目',
                description: 'カスタム実績項目を作成することも、他のプロジェクトの実績項目から選択することもできます。',
                side: 'top',
                align: 'start',
                showButtons: ['previous', 'next'],
            },
        },
        {
            element: '#projectCreateButton',
            popover: {
                title: 'プロジェクト作成ボタン',
                description: '必要な情報を入力したら、ここをクリックしてプロジェクトを作成します。',
                side: 'top',
                align: 'start',
            },
        },

        
        
    ]
    return steps
}
