import { User } from "./globalInterface";
export interface Theme {
    active: number;
    id: number;
    guidance: string;
    lesson_portfolio: Portfolio;
    title: string;
    discussion_date: Date | string;
    materials: LessonMaterial[];
}
export interface Portfolio {
    id: number;
    content: string;
    lesson_them_id: number;
    negative_feedback: string;
    noticed: string;
    portfolio_title: string;
    positive_feedback: string;
    public_content: string;
    public_title: string;
    lesson_sections: LessonSection[];
    status: number;
    user_id: number;
    lesson_form: LessonForm;
    user: User
    updated_at: string;
    claps: Clap[];
    lesson_theme: Theme
}
export interface LessonSection {
    id: number;
    content: string;
    material_id: number;
    portfolio_id: number;
    status: number;
    user_id: number;
    lesson_material: LessonMaterial;
}
export interface LessonMaterial {
    id: number;
    title: string;
    priority: number;
    lesson_theme_id: number;
    user_id: number;
    content: string;
    content_detailed: string;
    has_feedback: string;
}
interface LessonForm {
    answer1: string;
    answer2: string;
    answer3: string;
    content: string;
    lesson_theme_id: number;
    id: number;
    question1: string;
    question2: string;
    question3: string;
    user_id: number;
}
interface Clap {
    from_user: number;
    record_id: number;
}