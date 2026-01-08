import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#performanceManagement',
            popover: {
                title: '実績管理', 
                description: 'ここでプロジェクトの成果単位選択できます。カスタム成果単位も可能です。', 
                side: 'top', 
                align: 'start',
                showButtons: [],
            },
            onHighlighted: (el) => {
                el?.addEventListener('click', () => { driver.destroy()}, { once: true }); 
            }
        },
        
    ]
    return steps
}
