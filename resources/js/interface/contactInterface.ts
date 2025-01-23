import { User } from "./globalInterface"


export interface ContactRecord {
    id: number | null
    name: string	
    name_kana: string		
    company_name: string		
    company_name_kana: string		
    address: string		
    phone: string		
    fax: string		
    email: string		
    description: string		
    strategy: string		
    created_at: string		
    updated_at: string	
    icon_path: string | null	
    card_path: string | null
    creator?: User
    updater?: User
    data: string
    position: string
    type: ContactType | null
    contact_type_id: number | null
    pseudo_type: string
}

export interface ContactType{
    id: number | null
    title: string
}