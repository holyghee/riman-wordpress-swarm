const RelationshipGraph = require('./graph');

class SemanticNetwork {
  constructor() {
    this.network = new RelationshipGraph();
  }

  addEntity(id, type, attributes = {}) {
    const data = { type, ...attributes };
    this.network.addNode(id, data);
  }

  addRelationship(sourceId, targetId, type, weight = 1, metadata = {}) {
    const data = { type, ...metadata };
    this.network.addEdge(sourceId, targetId, weight, data);
  }

  getEntity(id) {
    return this.network.getNode(id);
  }

  getRelationship(sourceId, targetId) {
    return this.network.getEdge(sourceId, targetId);
  }

  updateRelationshipWeight(sourceId, targetId, weight) {
    this.network.updateEdgeWeight(sourceId, targetId, weight);
  }

  findRelatedEntities(id, minWeight = 0) {
    const neighbors = this.network.getNeighbors(id);
    const related = [];
    for (const neighbor of neighbors) {
      const edge = this.network.getEdge(id, neighbor) || this.network.getEdge(neighbor, id);
      if (edge && edge.weight >= minWeight) {
        related.push({
          entity: this.getEntity(neighbor),
          relationship: edge,
          entityId: neighbor
        });
      }
    }
    return related;
  }

  getGraph() {
    return this.network;
  }
}

module.exports = SemanticNetwork;