const Graph = require('graph-data-structure');

class RelationshipGraph {
  constructor() {
    this.graph = Graph();
  }

  addNode(id, data = {}) {
    if (!this.graph.hasNode(id)) {
      this.graph.addNode(id, data);
    }
  }

  getNode(id) {
    return this.graph.getNode(id);
  }

  removeNode(id) {
    this.graph.removeNode(id);
  }

  addEdge(from, to, weight = 1, data = {}) {
    if (!this.graph.hasEdge(from, to)) {
      this.graph.addEdge(from, to, weight);
      this.graph.setEdge(from, to, { ...data, weight });
    }
  }

  getEdge(from, to) {
    return this.graph.getEdge(from, to);
  }

  updateEdgeWeight(from, to, weight) {
    if (this.graph.hasEdge(from, to)) {
      const data = this.graph.getEdge(from, to) || {};
      this.graph.setEdge(from, to, { ...data, weight });
    }
  }

  removeEdge(from, to) {
    this.graph.removeEdge(from, to);
  }

  getNeighbors(id) {
    return this.graph.neighbors(id);
  }

  serialize() {
    return this.graph.serialize();
  }

  deserialize(serializedGraph) {
    this.graph = Graph(serializedGraph);
  }
}

module.exports = RelationshipGraph;