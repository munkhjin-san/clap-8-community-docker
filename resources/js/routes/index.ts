import type { RouteRecordRaw } from 'vue-router'
import { adminRoutes } from './admin'
import { boardRoutes } from './board'
import { communityRoutes } from './community'
import { dashboardRoutes } from './dashboard'
import { learningRoutes } from './learning'
import { miscRoutes } from './misc'
import { postRoutes } from './post'
import { projectRoutes } from './project'
import { userRoutes } from './user'

export const routes: RouteRecordRaw[] = [
    ...communityRoutes,
    ...boardRoutes,
    ...userRoutes,
    ...postRoutes,
    ...projectRoutes,
    ...adminRoutes,
    ...miscRoutes,
    ...learningRoutes,
    ...dashboardRoutes,
]