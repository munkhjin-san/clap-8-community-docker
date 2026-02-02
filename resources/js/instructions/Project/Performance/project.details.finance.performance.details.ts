import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#periodSelection',
            popover: {
                title: '対象月選択',
                description: 'どの月の目標かを選択します。',
                side: 'top',
                align: 'start',
                showButtons: ['previous', 'next'],
            },
        },
        {
            element: '#memberSelection',
            popover: {
                title: 'メンバー選択',
                description: '目標値を設定するメンバーを選択します。',
                side: 'top',
                align: 'start',
                showButtons: ['previous', 'next'],
            }
        },
        {
            element: '#resultInput',
            popover: {
                title: '成果単位入力',
                description: 'ここで各メンバーの成果単位を入力します。',
                side: 'top',
                align: 'start',
                showButtons: ['previous', 'next'],
            }
        },
        {
            element: '#noteInput',
            popover: {
                title: 'ノート入力',
                description: '必要に応じてノートを追加します。',
                side: 'top',
                align: 'start',
                showButtons: ['previous', 'next'],
            }
        },
        {
            element: '#saveButton',
            popover: {
                title: '保存',
                description: '設定が完了したら、ここをクリックして保存します。',
                side: 'top',
                align: 'start',
            }
        }
        
        
        
    ]
    return steps
}
