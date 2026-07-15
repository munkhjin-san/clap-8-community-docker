import { User } from "./globalInterface";
export interface LessonTheme {
    active: number;
    id: number;
    guidance: string;
    lesson_portfolio: Portfolio;
    title: string;
    discussion_date: Date | string;
    materials: LessonMaterial[];
}

export type Theme = LessonTheme;
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
    lesson_theme: LessonTheme
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
    lesson_theme_id: number | null;
    assistant_id: string | null;
    prompt_id: string | null;
    priority: number;
    user_id: number | null;
    updated_by: number | null;
    title: string | null;
    content: string | null;
    content_detailed: string | null;
    has_feedback: number;
    has_question: number | null;
    has_understand: number;
    material_type: string | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at: string | null;
    answer?: LessonAnswer | null;
    answers?: LessonAnswer[];
}
export interface LessonAnswer {
    id: number;
    material_id: number | null;
    user_id: number | null;
    answer: string | null;
    ai_review: string | null;
    cant_understand: string | null;
    reason_dnt_und: string | null;
    status: number | null;
    created_at: string | null;
    updated_at: string | null;
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
