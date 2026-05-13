import {
    SystemUpdateCategory,
    SystemUpdateDetailType,
    SystemUpdateStatus,
} from '@/interface/supportInterface';

export const categoryOptions: { value: SystemUpdateCategory; label: string }[] = [
    { value: 'maintenance_plan', label: 'メンテナンス予定' },
    { value: 'update_plan', label: 'アップデート予定' },
    { value: 'update_log', label: '更新履歴' },
    { value: 'notice', label: 'お知らせ' },
];

export const statusOptions: { value: SystemUpdateStatus; label: string }[] = [
    { value: 'draft', label: '下書き' },
    { value: 'scheduled', label: '予定' },
    { value: 'published', label: '公開中' },
    { value: 'completed', label: '完了' },
    { value: 'canceled', label: '中止' },
];

export const detailTypeOptions: { value: SystemUpdateDetailType; label: string }[] = [
    { value: 'new_feature', label: '新機能' },
    { value: 'improvement', label: '改善' },
    { value: 'error_fix', label: '不具合修正' },
    { value: 'security', label: 'セキュリティ' },
    { value: 'performance', label: 'パフォーマンス' },
    { value: 'maintenance', label: '保守' },
    { value: 'ui_change', label: '画面変更' },
    { value: 'known_issue', label: '既知の問題' },
    { value: 'notice', label: 'お知らせ' },
    { value: 'other', label: 'その他' },
];

export const detailTypeMeta: Record<SystemUpdateDetailType, { color: string; icon: string }> = {
    new_feature: {
        color: '#2563eb',
        icon: 'M12 1.5l2.9 6.1 6.7.9-4.9 4.8 1.2 6.7-5.9-3.2L6.1 20l1.2-6.7-4.9-4.8 6.7-.9L12 1.5z',
    },
    improvement: {
        color: '#16a34a',
        icon: 'M4 14l5 5L20 6l-1.5-1.3L9 16.1 5.5 12.6 4 14z',
    },
    error_fix: {
        color: '#be5a5a',
        icon: 'M12 2a10 10 0 100 20 10 10 0 000-20zm1 5v6h-2V7h2zm0 8v2h-2v-2h2z',
    },
    security: {
        color: '#7c3aed',
        icon: 'M12 2l8 3v6c0 5-3.4 8.9-8 11-4.6-2.1-8-6-8-11V5l8-3zm0 2.2L6 6.4V11c0 3.8 2.3 6.9 6 8.7 3.7-1.8 6-4.9 6-8.7V6.4l-6-2.2z',
    },
    performance: {
        color: '#ea580c',
        icon: 'M12 3a9 9 0 019 9h-2a7 7 0 10-14 0H3a9 9 0 019-9zm4.2 5.4l-3.1 4.5a2 2 0 11-1.7-1l4.8-3.5z',
    },
    maintenance: {
        color: '#475569',
        icon: 'M21.7 6.3l-3.2 1.3-1.8-1.8L18 2.6A7 7 0 0010.1 8.8L3 15.9a3 3 0 004.2 4.2l7.1-7.1a7 7 0 007.4-6.7z',
    },
    ui_change: {
        color: '#0891b2',
        icon: 'M4 5h16v12H4V5zm2 2v8h12V7H6zm3 11h6v2H9v-2z',
    },
    known_issue: {
        color: '#b45309',
        icon: 'M12 2l10 18H2L12 2zm0 4.1L5.4 18h13.2L12 6.1zM11 10h2v4h-2v-4zm0 5h2v2h-2v-2z',
    },
    notice: {
        color: '#0f766e',
        icon: 'M4 4h16v14H7l-3 3V4zm2 2v10.2l.2-.2H18V6H6zm2 3h8v2H8V9zm0 4h6v2H8v-2z',
    },
    other: {
        color: '#64748b',
        icon: 'M12 8a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z',
    },
};

export const labelFromOptions = <T extends string>(options: { value: T; label: string }[], value: T) => {
    return options.find((option) => option.value === value)?.label ?? value;
};
