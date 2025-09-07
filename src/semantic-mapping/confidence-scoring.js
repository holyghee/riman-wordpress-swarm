class ConfidenceScorer {
  constructor(network) {
    this.network = network;
  }

  /**
   * Calculates a confidence score for a relationship based on various factors.
   * @param {string} sourceId - The ID of the source entity.
   * @param {string} targetId - The ID of the target entity.
   * @param {object} evidence - Evidence for the relationship (e.g., from similarity matching).
   * @returns {number} A confidence score between 0 and 1.
   */
  calculateScore(sourceId, targetId, evidence = {}) {
    let score = 0;
    let factors = 0;

    if (evidence.similarity) {
      score += evidence.similarity;
      factors++;
    }

    if (evidence.sourceReliability) {
      score += evidence.sourceReliability;
      factors++;
    }
    
    // Consider path-based features from the graph
    const pathScore = this.calculatePathScore(sourceId, targetId);
    if (pathScore > 0) {
        score += pathScore;
        factors++;
    }

    return factors > 0 ? score / factors : 0;
  }
  
  /**
   * Calculates a score based on paths between two entities in the network.
   * Shorter and stronger paths contribute more to the score.
   * @param {string} sourceId 
   * @param {string} targetId 
   * @returns {number} Score between 0 and 1.
   */
  calculatePathScore(sourceId, targetId) {
      const paths = this.network.getGraph().graph.shortestPath(sourceId, targetId, { all: true });
      if (!paths || paths.length === 0) {
          return 0;
      }

      let totalPathScore = 0;
      for (const path of paths) {
          let pathWeight = 1;
          for(let i = 0; i < path.length - 1; i++) {
              const edge = this.network.getRelationship(path[i], path[i+1]);
              pathWeight *= (edge ? edge.weight : 0);
          }
          // Longer paths are penalized
          totalPathScore += pathWeight / (path.length - 1);
      }

      // Normalize based on number of paths, but cap to 1
      return Math.min(1, totalPathScore);
  }

  updateRelationshipWithConfidence(sourceId, targetId, evidence = {}) {
    const score = this.calculateScore(sourceId, targetId, evidence);
    this.network.updateRelationshipWeight(sourceId, targetId, score);
    return score;
  }
}

module.exports = ConfidenceScorer;