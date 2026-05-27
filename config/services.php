<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'sns_emergency_topic_arn' => env('AWS_SNS_EMERGENCY_TOPIC_ARN'),
    ],
    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_URL'),
    ],
    'twitter' => [
            'client_id'     => env('TWITTER_CLIENT_ID'),
            'client_secret' => env('TWITTER_CLIENT_SECRET'),
            'redirect'      => env('TWITTER_URL'),
        ],
    'google' => [
            'client_id'     => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect'      => env('GOOGLE_URL'),
            'credentials_json' => env('GOOGLE_SHEETS_CREDENTIALS'),
            'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
            'gemini_api_key' => env('GEMINI_API_KEY'),
            'gemini_url' => env('GEMINI_URL'),
            'receipt_ocr_model' => env('GEMINI_RECEIPT_OCR_MODEL', 'models/gemini-3-flash-preview'),
            'contact_card_split_model' => env('GEMINI_CONTACT_CARD_SPLIT_MODEL', 'models/gemini-3-flash-preview'),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'chatkit_workflow_id' => env('OPENAI_CHATKIT_WORKFLOW_ID'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'compare_model' => env('OPENAI_COMPARE_SUMMARY_MODEL', 'gpt-4.1-mini'),
        'prompts' => [
            'message_correction' => env('OPENAI_PROMPT_MESSAGE_CORRECTION_ID', ''),
            'project_description_generation' => env('OPENAI_PROMPT_PROJECT_DESCRIPTION_GENERATION_ID', ''),
            'project_miso_generation' => env('OPENAI_PROMPT_PROJECT_MISO_GENERATION_ID', ''),
            'project_task_generation' => env('OPENAI_PROMPT_PROJECT_TASK_GENERATION_ID', ''),
            'project_goal_generation' => env('OPENAI_PROMPT_PROJECT_GOAL_GENERATION_ID', ''),
            'project_salary_issue_generation' => env('OPENAI_PROMPT_PROJECT_SALARY_ISSUE_GENERATION_ID', ''),
            'project_salary_issue_guideline_generation' => env('OPENAI_PROMPT_PROJECT_SALARY_ISSUE_GUIDELINE_GENERATION_ID', ''),
            'project_member_assign_evaluation' => env('OPENAI_PROMPT_PROJECT_MEMBER_ASSIGN_EVALUATION', ''),
            'project_risk_assessment_generation' => env('OPENAI_PROMPT_PROJECT_RISK_ASSESSMENT_GENERATION_ID', ''),
            'lesson_portfolio_review' => env('OPENAI_PROMPT_LESSON_PORTFOLIO_REVIEW_ID', ''),
            'legal_quick_review' => env('OPENAI_PROMPT_LEGAL_QUICK_REVIEW_ID'),
            'legal_deep_review' => env('OPENAI_PROMPT_LEGAL_DEEP_REVIEW_ID'),
            'challenge_suggestion' => env('OPENAI_PROMPT_CHALLENGE_SUGGESTION'),
            'normal_challenge_suggestion' => env('OPENAI_PROMPT_NORMAL_CHALLENGE_SUGGESTION'),
            'project_member_assign_evaluation' => env('OPENAI_PROMPT_PROJECT_MEMBER_ASSIGN_EVALUATION'),
        ]
    ],
    'VAPID' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT'),
    ],
    'socket' => [
        'internal_url' => env('VITE_SOCKET_URL'),
        'internal_token' => env('VITE_SOCKET_TOKEN'),
    ],


];
