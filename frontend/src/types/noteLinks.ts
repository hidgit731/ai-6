export interface NoteRef {
    id: string
    title: string
}

export interface NoteLinksResponse {
    incoming: NoteRef[]
    outgoing: NoteRef[]
}

export interface GraphNode {
    id: string
    title: string
}

export interface GraphEdge {
    source: string
    target: string
}

export interface GraphResponse {
    nodes: GraphNode[]
    edges: GraphEdge[]
}
