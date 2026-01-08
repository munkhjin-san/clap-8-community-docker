import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#financeSelection',
            popover: {
                title: '予算・実績', 
                description: '収支確認、実績管理確認など。', 
                side: 'left', 
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
