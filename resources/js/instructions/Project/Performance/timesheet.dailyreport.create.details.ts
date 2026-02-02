import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#timesheetProjectSelect',
            popover: {
                title: 'プロジェクト選択', 
                description: '実績管理が有効なプロジェクトを選択します。', 
                side: 'left', 
                align: 'start',
                showButtons: ['previous', 'next'],
            },                 
        },
        {
            element: '#performanceReport',
            popover: {
                title: '実績報告',
                description: '実績値を入力します。',
                side: 'left', 
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
