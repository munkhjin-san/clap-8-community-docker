import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#create-button',
            popover: {
                title: '日報作成', 
                description: 'ここで新しい日報を作成できます。', 
                side: 'left', 
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
