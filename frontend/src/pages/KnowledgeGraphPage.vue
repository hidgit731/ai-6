<script setup lang="ts">
import { useTemplateRef, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import * as d3 from 'd3'
import { useGraphStore } from '@/stores/graph'
import type { GraphNode, GraphEdge } from '@/types/noteLinks'

interface SimNode extends GraphNode, d3.SimulationNodeDatum {
    x?: number
    y?: number
}

interface SimLink extends d3.SimulationLinkDatum<SimNode> {
    source: SimNode | string
    target: SimNode | string
}

const router = useRouter()
const graphStore = useGraphStore()
const svgEl = useTemplateRef<SVGSVGElement>('svgEl')

let simulation: d3.Simulation<SimNode, SimLink> | null = null

function buildGraph(nodes: GraphNode[], edges: GraphEdge[]): void {
    const svgNode = svgEl.value
    if (!svgNode) return

    const width = svgNode.clientWidth || 800
    const height = svgNode.clientHeight || 600

    // Clear previous render
    d3.select(svgNode).selectAll('*').remove()

    const svg = d3.select(svgNode)

    // Root group for pan/zoom
    const g = svg.append('g')

    // Zoom behavior
    const zoom = d3.zoom<SVGSVGElement, unknown>()
        .scaleExtent([0.2, 4])
        .on('zoom', (event) => {
            g.attr('transform', event.transform)
        })
    svg.call(zoom)

    const simNodes: SimNode[] = nodes.map((n) => ({ ...n }))
    const nodeById = new Map(simNodes.map((n) => [n.id, n]))

    const simLinks: SimLink[] = edges
        .filter((e) => nodeById.has(e.source) && nodeById.has(e.target))
        .map((e) => ({
            source: nodeById.get(e.source)!,
            target: nodeById.get(e.target)!,
        }))

    simulation = d3.forceSimulation<SimNode>(simNodes)
        .force('link', d3.forceLink<SimNode, SimLink>(simLinks).id((d) => d.id).distance(120))
        .force('charge', d3.forceManyBody<SimNode>().strength(-300))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collide', d3.forceCollide<SimNode>(30).iterations(5))
        .alphaDecay(0.08)

    // Draw edges
    const link = g.append('g')
        .selectAll<SVGLineElement, SimLink>('line')
        .data(simLinks)
        .join('line')
        .style('stroke', '#ccc')
        .style('stroke-width', '1.5px')

    // Draw nodes
    const node = g.append('g')
        .selectAll<SVGGElement, SimNode>('g')
        .data(simNodes)
        .join('g')
        .style('cursor', 'pointer')
        .on('click', (_event, d) => {
            router.push(`/notes/${d.id}`)
        })
        .call(
            d3.drag<SVGGElement, SimNode>()
                .on('start', (event, d) => {
                    if (!event.active) simulation?.alphaTarget(0.3).restart()
                    d.fx = d.x
                    d.fy = d.y
                })
                .on('drag', (event, d) => {
                    d.fx = event.x
                    d.fy = event.y
                })
                .on('end', (event, d) => {
                    if (!event.active) simulation?.alphaTarget(0)
                    d.fx = null
                    d.fy = null
                })
        )

    node.append('circle')
        .attr('r', 14)
        .attr('fill', '#4a90d9')
        .attr('stroke', '#2c6fad')
        .attr('stroke-width', '2')

    node.append('text')
        .attr('dy', '28')
        .attr('text-anchor', 'middle')
        .attr('fill', '#333333')
        .attr('font-size', '12')
        .attr('font-family', 'sans-serif')
        .style('pointer-events', 'none')
        .style('user-select', 'none')
        .text((d) => d.title.length > 22 ? d.title.slice(0, 20) + '…' : d.title)

    simulation.on('tick', () => {
        link
            .attr('x1', (d) => (d.source as SimNode).x ?? 0)
            .attr('y1', (d) => (d.source as SimNode).y ?? 0)
            .attr('x2', (d) => (d.target as SimNode).x ?? 0)
            .attr('y2', (d) => (d.target as SimNode).y ?? 0)

        node.attr('transform', (d) => `translate(${d.x ?? 0},${d.y ?? 0})`)
    })
}

onMounted(async () => {
    await graphStore.fetchGraph()
    if (graphStore.graphData) {
        buildGraph(graphStore.graphData.nodes, graphStore.graphData.edges)
    }
})

watch(
    () => graphStore.graphData,
    (data) => {
        if (data && svgEl.value) {
            buildGraph(data.nodes, data.edges)
        }
    },
)

onBeforeUnmount(() => {
    simulation?.stop()
})
</script>

<template>
    <div class="knowledge-graph-page">
        <div class="page-header">
            <button class="btn-back" @click="$router.back()">← Назад</button>
            <h1 class="page-title">Knowledge Graph</h1>
        </div>

        <div v-if="graphStore.loading" class="loading">Загрузка графа...</div>

        <div
            v-else-if="graphStore.graphData && graphStore.graphData.nodes.length === 0"
            class="empty-state"
        >
            No notes yet. Create some notes with <code>[[wiki-links]]</code> to see the graph.
        </div>

        <svg
            v-else
            ref="svgEl"
            class="graph-svg"
        />
    </div>
</template>

<style scoped>
.knowledge-graph-page {
    display: flex;
    flex-direction: column;
    height: 100vh;
    padding: 1rem;
    box-sizing: border-box;
    gap: 0.75rem;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.btn-back {
    background: none;
    border: 1px solid #ddd;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    cursor: pointer;
    color: #555;
}

.btn-back:hover {
    background: #f5f5f5;
}

.page-title {
    font-size: 1.2rem;
    margin: 0;
    color: #333;
}

.graph-svg {
    flex: 1;
    width: 100%;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: #fafafa;
}


.loading {
    text-align: center;
    color: #888;
    padding: 3rem;
}

.empty-state {
    text-align: center;
    color: #aaa;
    padding: 3rem;
    font-size: 1.1rem;
}

.empty-state code {
    background: #f4f4f4;
    padding: 0.15em 0.4em;
    border-radius: 3px;
    color: #555;
}
</style>
