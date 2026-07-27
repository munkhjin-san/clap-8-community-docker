import { defineStore } from "pinia";
import { useAuthUserStore } from "./auth";
import axios from "axios";
import { ref, computed } from "vue";

type PostBadgeItem = {
    id: number
    title: string | null
}

type PostNoticeType = 'changed' | 'progress_report' | 'last_chargeable'

type PostNoticeItems = Record<PostNoticeType, PostBadgeItem[]>

interface State {
    board: any[]
    post: {
        changed: number,
        created: number,
        changed_ids: number[],
        changed_items: PostBadgeItem[],
        progress_report: number,
        progress_report_ids: number[],
        progress_report_items: PostBadgeItem[],
        last_chargeable: number,
        last_chargeable_ids: number[],
        last_chargeable_items: PostBadgeItem[],
    }
    task: number[]
    members_goals: any[]
    managers_goals: any[]
    salary_issue: any[]
    asset: []
    task_comment: {project_id: number, task_id: number, comments: number}[],
    finance_comment: {total_unread: number, projects: {project_id: number, project_name: string, total_unread: number, period_counts: {[period: string]: number}}[]},
    goal_issue_comment: [],
    contact_comment: [],
    contact_batch_notification_count: number,
    boardBadgeFetchedAt: number | null,
    boardBadgeRequest: Promise<void> | null,
    communityBadge: boolean;
    project_report: {
        records: [{
            project_record_id: number | null,
            unread_count: number
            types: [{
                type: string,
                unread_count: number
            }]
        }],
        total: number
    },
    check_item_confirm: {
        total: number,
        records: {
            project_id: number,
            count: number
        }[]
    },
    kintone_contract_changes: {
        total: number,
        records: {
            project_id: number,
            record_id: number,
            count: number
        }[],
        projects: {
            project_id: number,
            count: number
        }[]
    }
}
const BOARD_BADGE_CACHE_MS = 2000;
const emptyPostNoticeItems = (): PostNoticeItems => ({
    changed: [],
    progress_report: [],
    last_chargeable: [],
});

export const useBadgeStore = defineStore('badge', () => {
    // State
    const board = ref<any[]>([]);
    const post = ref({
        changed: 0,
        created: 0,
        changed_ids: [] as number[],
        changed_items: [] as PostBadgeItem[],
        progress_report: 0,
        progress_report_ids: [] as number[],
        progress_report_items: [] as PostBadgeItem[],
        last_chargeable: 0,
        last_chargeable_ids: [] as number[],
        last_chargeable_items: [] as PostBadgeItem[],
    });
    const postNoticeItems = ref<PostNoticeItems>(emptyPostNoticeItems());
    const task = ref<number[]>([]);
    const notice = ref(0);
    const members_goals = ref<any[]>([]);
    const managers_goals = ref<any[]>([]);
    const salary_issue = ref<any[]>([]);
    const asset = ref<any[]>([]);
    const task_comment = ref<{project_id: number, task_id: number, comments: number}[]>([]);
    const finance_comment = ref<{total_unread: number, projects: {project_id: number, project_name: string, total_unread: number, period_counts: {[period: string]: number}}[]}>({total_unread: 0, projects: []});
    const goal_issue_comment = ref<any[]>([]);
    const contact_comment = ref<any[]>([]);
    const contact_batch_notification_count = ref(0);
    // アプリ (Flow): unread notifications + records awaiting this user's action, summed across
    // every visible app. Comes from badge_summary so the side-menu badge is correct on first
    // paint, before the app list has ever been fetched.
    const flow = ref<{ unread: number; pending: number; total: number }>({ unread: 0, pending: 0, total: 0 });
    const boardBadgeFetchedAt = ref<number | null>(null);
    const boardBadgeRequest = ref<Promise<void> | null>(null);
    const communityBadge = ref(false);
    const project_report = ref<{records: {project_record_id: number | null, unread_count: number, types: {type: string, unread_count: number}[]}[], total: number}>({records: [], total: 0});
    const check_item_confirm = ref<{total: number, records: {project_id: number, count: number}[]}>({total: 0, records: []});
    const kintone_contract_changes = ref<{
        total: number,
        records: {project_id: number, record_id: number, count: number}[],
        projects: {project_id: number, count: number}[]
    }>({total: 0, records: [], projects: []});
    // Actions
    function setTaskBadge(payload: number[]) {
        task.value = payload;
    }

    async function getGoalIssueCommentBadge() {
        const data = await axios.get('/goal_issue_comment_badge').then(response => response.data);
        goal_issue_comment.value = data;
    }

    async function updatePostBadge(which: string) {
        const response = await axios.patch('/post_badge', {which: which});
        mergePostNoticeItems(post.value);
        mergePostNoticeItems(response.data);
        post.value = response.data;
    }
    function mergePostNoticeItems(payload: Partial<{
        changed_items: PostBadgeItem[],
        progress_report_items: PostBadgeItem[],
        last_chargeable_items: PostBadgeItem[],
    }>) {
        const sources: Record<PostNoticeType, PostBadgeItem[] | undefined> = {
            changed: payload.changed_items,
            progress_report: payload.progress_report_items,
            last_chargeable: payload.last_chargeable_items,
        };

        (Object.keys(sources) as PostNoticeType[]).forEach(type => {
            const incoming = sources[type] ?? [];
            if (!incoming.length) return;

            const existing = new Map(postNoticeItems.value[type].map(item => [item.id, item]));
            incoming.forEach(item => existing.set(item.id, item));
            postNoticeItems.value[type] = Array.from(existing.values());
        });
    }
    function clearPostNoticeItem(type: PostNoticeType, id: number) {
        postNoticeItems.value[type] = postNoticeItems.value[type].filter(item => item.id !== id);
    }
    function clearPostNoticeItems() {
        postNoticeItems.value = emptyPostNoticeItems();
    }

    async function getNoticeBadge() {
        const auth = useAuthUserStore();
        if (!auth.isPartner && !auth.isRegistered) {
            const response = await axios.get('/notice_badge');
            notice.value = response.data;
        }
    }

    async function getBoardBadge(force = false, src?: string) {
        const now = Date.now();
        if (!force && boardBadgeFetchedAt.value && (now - boardBadgeFetchedAt.value) < BOARD_BADGE_CACHE_MS) {
            return boardBadgeRequest.value ?? Promise.resolve();
        }
        if (!boardBadgeRequest.value) {
            boardBadgeRequest.value = axios.get('/board_badge').then(response => response.data).then(data => {
                board.value = data;
                boardBadgeFetchedAt.value = Date.now();
            }).finally(() => {
                boardBadgeRequest.value = null;
            });
        }
        return boardBadgeRequest.value;
    }

    async function updateBoardBadge(id: number) {
        const data = await axios.patch('/board_badge', {board_id: id}).then(response => response.data);
        board.value = data;
        boardBadgeFetchedAt.value = Date.now();
    }

    async function getTaskBadge() {
        const data = await axios.get('/task_badge').then(response => response.data);
        task.value = data;
    }

    async function getMembersGoalsBadge() {
        const data = await axios.get('/get_members_goals_badge').then(response => response.data);
        members_goals.value = data;
    }

    async function getManagersGoalsBadge() {
        const data = await axios.get('/get_managers_goals_badge').then(response => response.data);
        managers_goals.value = data;
    }

    async function getSalaryIssueBadge() {
        const data = await axios.get('/get_salary_issue_badge').then(response => response.data);
        salary_issue.value = data;
    }

    async function getAssetBadge() {
        const data = await axios.get('/get_asset_badge').then(response => response.data);
        asset.value = data;
    }

    async function getTaskCommentBadge() {
        const data = await axios.get('/get_task_comment_badge').then(response => response.data);
        task_comment.value = data;
    }

    async function getFinanceCommentBadge() {
        const data = await axios.get('/projects/finance/unread-badges').then(response => response.data);
        finance_comment.value = data;
    }
    async function clearProjectReportBadge() {
        const response = await axios.get('/clear_project_report_badge').then(response => response.data);
        project_report.value = response;
    }
    async function clearProjectConfirmBadge() {
        const response = await axios.get('/clear_project_confirm_badge').then(response => response.data);
        check_item_confirm.value = response;
    }
    async function clearGoalIssue({column, value}: {column: string, value: any}) {
        const response = await axios.post('/clear_goal_issue_badge', {column: column, value: value});
        goal_issue_comment.value = response.data;
    }

    async function getContactCommentBadge() {
        const data = await axios.get('/get_contact_comment_badge').then(response => response.data);
        contact_comment.value = data;
    }

    async function getTodayReadableBadge() {
        const data = await axios.get('/get_today_readable').then(response => response.data);
        communityBadge.value = data.has_unread;
    }

    async function checkKintoneContractChange(payload: { project_id: number, record_id: number }) {
        const data = await axios.post('/kintone_contract_change/check', payload).then(response => response.data);
        kintone_contract_changes.value = data;
    }

    /** Portal/record screens know the live numbers — let them correct the badge immediately
     *  instead of waiting out badge_summary's 60s cache. */
    function setFlowBadge(next: { unread?: number; pending?: number }) {
        const unread = next.unread ?? flow.value.unread;
        const pending = next.pending ?? flow.value.pending;
        flow.value = { unread, pending, total: unread + pending };
    }

    async function getbadgeSummary() {
        const data = await axios.get('/badge_summary').then(response => response.data);
        goal_issue_comment.value = data.goal_issue_comment;
        mergePostNoticeItems(data.post);
        post.value = data.post;
        members_goals.value = data.members_goals;
        managers_goals.value = data.managers_goals;
        salary_issue.value = data.salary_issue;
        asset.value = data.asset;
        task_comment.value = data.task_comment;
        finance_comment.value = data.finance_comment;
        goal_issue_comment.value = data.goal_issue_comment;
        contact_comment.value = data.contact_comment;
        contact_batch_notification_count.value = data.contact_batch_notification_count ?? 0;
        communityBadge.value = data.today_readable.has_unread;
        project_report.value = data.project_report;
        check_item_confirm.value = data.check_item_confirm;
        kintone_contract_changes.value = data.kintone_contract_changes ?? { total: 0, records: [], projects: [] };
        flow.value = data.flow ?? { unread: 0, pending: 0, total: 0 };
    }

    // Getters
    const activeUsersBoardBadge = computed(() => {
        const auth = useAuthUserStore();
        const activeUser = auth.activeUser.id;
        return board.value.find(ob => ob.user_id == activeUser)?.list;
    });

    const totalBoardBadge = computed(() => {
        return (userId: number) => {
            const filtered = board.value.find((data) => data.user_id === userId);
            if (filtered) {
                const list = filtered.list;
                let value = 0;
                for (var i in list) {
                    value = value + list[i];
                }
                return value;
            }
            return 0;
        };
    });

    const totalUserBadge = computed(() => {
        const auth = useAuthUserStore();
        return (userId: number) => {
            let value = 0;
            const filtered = board.value.find((data) => data.user_id === userId);
            if (filtered) {
                const list = filtered.list;
                for (var i in list) {
                    value = value + list[i];
                }
            }
            if (auth.id == userId) {
                const postBadge = post.value.changed + post.value.created + post.value.last_chargeable;
                value = value + postBadge;
            }
            return value;
        };
    });

    const goalAndSalaryTotal = computed(() => {
        return managers_goals.value.length + members_goals.value.length + salary_issue.value.length;
    });

    const projectCommentTotal = computed(() => {
        return task_comment.value.length + finance_comment.value.total_unread + goal_issue_comment.value.length + project_report.value.total;
    });

    const projectTotal = computed(() => {
        return goalAndSalaryTotal.value + asset.value.length + check_item_confirm.value.total + kintone_contract_changes.value.total;
    });

    const sumOfAll = computed(() => {
        const auth = useAuthUserStore();
        let sum = 0;
        board.value.forEach((p: { list: { [x: string]: number; }; }) => {
            for (var i in p.list) {
                sum = sum + p.list[i];
            }
        });
        const projectBadge = projectTotal.value + projectCommentTotal.value;
        // const remindBadge = remind.total
        const postBadge = auth.activeUser?.linkable || auth.user?.linkable ? 0 : (post.value.changed + post.value.created + post.value.last_chargeable);
        sum = sum + postBadge + projectBadge + contact_batch_notification_count.value;
        return sum;
    });

    const goalsBadge = computed(() => {
        return [...managers_goals.value, ...members_goals.value];
    });

    const salaryIssueBadge = computed(() => {
        return salary_issue.value;
    });

    const goalsBadgeByFilter = computed(() => {
        return (filterData: {by: string, value: any}[]) => {
            const userGoals = [...managers_goals.value, ...members_goals.value];
            return userGoals.filter((goal) => {
                return filterData.every((filter) => goal[filter.by] === filter.value);
            });
        };
    });

    const salaryIssueByFilter = computed(() => {
        return (filterData: {by: string, value: any}[]) => {
            const userIssues = salary_issue.value;
            return userIssues.filter((issue) => {
                return filterData.every((filter) => issue[filter.by] === filter.value);
            });
        };
    });

    const taskCommentBadgeByFilter = computed(() => {
        return (filterData: {by: string, value: any}[]) => {
            const userComments = task_comment.value;
            return userComments.filter((comment) => {
                return filterData.every((filter) => comment[filter.by as keyof typeof comment] === filter.value);
            });
        };
    });

    const financeCommentBadgeByFilter = computed(() => {``
        return (filterData: { by: string; value: any }) =>
            finance_comment.value?.projects?.find(
                (comment) => comment.project_id === filterData.value
            ) ?? null;
    });

    // 次の未読コメントの遷移先を求める。
    // 1) まず現在のプロジェクトの残り未読月（時系列順、無ければ最古へループ）
    // 2) 無ければ他プロジェクトを「最も早い未読月」が近い順にたどる
    const nextFinanceUnread = computed(() => {
        return (
            currentProjectId: number,
            currentPeriod: string
        ): { project_id: number; project_name: string; period: string; count: number; sameProject: boolean } | null => {
            const projects = finance_comment.value?.projects ?? [];
            const unreadMonths = (p: { period_counts: { [period: string]: number } }) =>
                Object.keys(p.period_counts ?? {})
                    .filter((m) => (p.period_counts[m] ?? 0) > 0)
                    .sort(); // yyyy-MM は文字列ソートで時系列順

            // 1) 現在のプロジェクト内の次の未読月
            const current = projects.find((p) => p.project_id === currentProjectId);
            if (current) {
                const months = unreadMonths(current).filter((m) => m !== currentPeriod);
                const target = months.find((m) => m > currentPeriod) ?? months[0];
                if (target) {
                    return {
                        project_id: currentProjectId,
                        project_name: current.project_name,
                        period: target,
                        count: current.period_counts[target] ?? 0,
                        sameProject: true,
                    };
                }
            }

            // 2) 他プロジェクト（最も早い未読月が近い順）
            const others = projects
                .filter((p) => p.project_id !== currentProjectId)
                .map((p) => ({ p, months: unreadMonths(p) }))
                .filter((x) => x.months.length > 0)
                .sort((a, b) =>
                    a.months[0] < b.months[0] ? -1 : a.months[0] > b.months[0] ? 1 : a.p.project_id - b.p.project_id
                );
            if (others.length) {
                const { p, months } = others[0];
                return {
                    project_id: p.project_id,
                    project_name: p.project_name,
                    period: months[0],
                    count: p.period_counts[months[0]] ?? 0,
                    sameProject: false,
                };
            }

            return null;
        };
    });

    const assetsBadgeByFilter = computed(() => {
        return (filterData: {by: string, value: any}[]) => {
            const userAssets = asset.value;
            return userAssets.filter((asset) => {
                return filterData.every((filter) => asset[filter.by] === filter.value);
            });
        };
    });

    const goalIssueCommentBadgeByFilter = computed(() => {
        return (filterData: {by: string, value: any}[]) => {
            const userComments = goal_issue_comment.value;
            return userComments.filter((comment) => {
                return filterData.every((filter) => comment[filter.by] === filter.value);
            });
        };
    });

    const contactBadge = computed(() => {
        return contact_comment.value;
    });
    const contactBatchNotificationCount = computed(() => {
        return contact_batch_notification_count.value;
    });

    const communityBadgeStatus = computed(() => {
        return communityBadge.value;
    });
    const projectReportMapByType = computed(() => {
        const map: Record<number, Record<string, number>> = {};

        project_report.value.records.forEach(record => {
            const recordId = record.project_record_id;
            if (recordId == null) return;

            map[recordId] ??= {};

            record.types.forEach(t => {
                if (t.type == null) return; // skip null
                map[recordId][t.type] = t.unread_count;
            });
        });
        return map;
    })
    const projectReportMap = computed(() => {
        const map: {[project_record_id: number]: number} = {};
        project_report.value.records.forEach(record => {
            if (record.project_record_id) {
                map[record.project_record_id] = record.unread_count;
            }
        })
        return map;
    })
    const checkItemConfirmByFilter = computed(() => {
        const map: {[project_record_id: number]: number} = {};
        check_item_confirm.value.records.forEach(record => {
            if (record.project_id) {
                map[record.project_id] = record.count;
            }
        })
        return map;
    })
    const kintoneContractChangesByProject = computed(() => {
        const map: {[project_id: number]: number} = {};
        kintone_contract_changes.value.projects.forEach(record => {
            map[record.project_id] = record.count;
        });
        return map;
    });
    const kintoneContractRecordIdsByProject = computed(() => {
        const map: Record<number, Set<number>> = {};
        kintone_contract_changes.value.records.forEach(record => {
            map[record.project_id] ??= new Set<number>();
            map[record.project_id].add(record.record_id);
        });
        return map;
    });
    return {
        // State
        board,
        post,
        postNoticeItems,
        task,
        notice,
        members_goals,
        managers_goals,
        salary_issue,
        asset,
        task_comment,
        finance_comment,
        goal_issue_comment,
        contact_comment,
        contact_batch_notification_count,
        flow,
        setFlowBadge,
        boardBadgeFetchedAt,
        boardBadgeRequest,
        communityBadge,
        kintone_contract_changes,
        // Actions
        setTaskBadge,
        getGoalIssueCommentBadge,
        updatePostBadge,
        clearPostNoticeItem,
        clearPostNoticeItems,
        getNoticeBadge,
        getBoardBadge,
        updateBoardBadge,
        getTaskBadge,
        getMembersGoalsBadge,
        getManagersGoalsBadge,
        getSalaryIssueBadge,
        getAssetBadge,
        getTaskCommentBadge,
        getFinanceCommentBadge,
        clearGoalIssue,
        getContactCommentBadge,
        getTodayReadableBadge,
        getbadgeSummary,
        clearProjectReportBadge,
        clearProjectConfirmBadge,
        checkKintoneContractChange,
        // Getters
        activeUsersBoardBadge,
        totalBoardBadge,
        totalUserBadge,
        sumOfAll,
        goalsBadge,
        salaryIssueBadge,
        goalsBadgeByFilter,
        salaryIssueByFilter,
        taskCommentBadgeByFilter,
        financeCommentBadgeByFilter,
        nextFinanceUnread,
        goalAndSalaryTotal,
        projectTotal,
        projectCommentTotal,
        assetsBadgeByFilter,
        goalIssueCommentBadgeByFilter,
        contactBadge,
        contactBatchNotificationCount,
        communityBadgeStatus,
        projectReportMap,
        projectReportMapByType,
        checkItemConfirmByFilter,
        kintoneContractChangesByProject,
        kintoneContractRecordIdsByProject,
    };
})
