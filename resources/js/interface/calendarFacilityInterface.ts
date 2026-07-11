export type CalendarFacilityType = 'room' | 'car'

export interface CalendarFacilitySetting {
    id: number
    type: CalendarFacilityType
    slot: number
    label: string
    active: boolean
    updated_at: string | null
}
