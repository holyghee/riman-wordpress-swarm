const RelationshipGraph = require('./graph');
const SemanticNetwork = require('./semantic-network');
const ConfidenceScorer = require('./confidence-scoring');
const SimilarityMatcher = require('./similarity-matching');
const RelationshipValidator = require('./validation');
const SemanticQuery = require('./query');
const GraphVisualizer = require('./visualization');

/**
 * The main entry point for the Semantic Mapping core.
 * This class orchestrates the different components of the system.
 */
class SemanticMappingCore {
  constructor(validationConfig = {}) {
    this.network = new SemanticNetwork();
    this.scorer = new ConfidenceScorer(this.network);
    this.matcher = new SimilarityMatcher();
    this.validator = new RelationshipValidator(validationConfig);
    this.queryEngine = new SemanticQuery(this.network);
    this.visualizer = new GraphVisualizer(this.network);
  }

  // Expose components
  getNetwork() {
    return this.network;
  }

  getScorer() {
    return this.scorer;
  }

  getMatcher() {
    return this.matcher;
  }

  getValidator() {
    return this.validator;
  }

  getQueryEngine() {
    return this.queryEngine;
  }

  getVisualizer() {
    return this.visualizer;
  }
  
  /**
   * A high-level method to add a relationship with confidence scoring.
   * @param {string} sourceId 
   * @param {string} targetId 
   * @param {string} type 
   * @param {object} evidence 
   */
  addRelationship(sourceId, targetId, type, evidence = {}) {
      const score = this.scorer.calculateScore(sourceId, targetId, evidence);
      this.network.addRelationship(sourceId, targetId, type, score);
  }
}

module.exports = {
  SemanticMappingCore,
  RelationshipGraph,
  SemanticNetwork,
  ConfidenceScorer,
  SimilarityMatcher,
  RelationshipValidator,
  SemanticQuery,
  GraphVisualizer,
};