import { useApi } from "./api";

export type ShareState = {
    nodeId: string;
    visibility: 'public' | 'private';
    members: number[];
}
const api = useApi();
export const sharingApi = {
    get: (id: string): Promise<ShareState> =>
     api.get(`/drive/${id}/sharing`),

    update: (id: string, payload: {
        visibility: 'public'|'private',
        members: number[],
    }) => api.put(`/drive/${id}/sharing`, payload)
}