import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#projectCreateAchievements',
            popover: {
                title: 'プロジェクト実績管理機能', 
                description: 'プロジェクトの実績管理をONしてください。', 
                side: 'bottom', 
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
