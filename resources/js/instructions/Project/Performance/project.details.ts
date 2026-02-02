import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#projectTable',
            popover: {
                title: 'プロジェクトを選択', 
                description: '実績管理をONしたプロジェクトを選択してください。', 
                side: 'top', 
                align: 'start',
                showButtons: [],
            },                 
            onHighlighted: (el) => {
                el?.addEventListener('click', () => { driver.destroy()}, { once: true }); 
            }
        }
    ]
    return steps
}
