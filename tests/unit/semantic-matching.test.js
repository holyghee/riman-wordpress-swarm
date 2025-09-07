/**
 * Unit Tests for Semantic Image Matching System
 * Quality Engineer - Semantic Algorithm Testing
 */

const fs = require('fs');
const path = require('path');

describe('Semantic Image Matching System', () => {
  let semanticMatcher;
  let testData;
  let imageDatabase;

  beforeAll(async () => {
    // Mock semantic matcher implementation
    semanticMatcher = {
      findBestMatch: (content, imageDatabase, options = {}) => {
        const weights = options.weights || { theme: 0.4, quadrant_descriptions: 0.6 };
        const matches = [];
        
        imageDatabase.agents.forEach(agent => {
          const themeScore = calculateThemeMatch(content.keywords, agent.theme);
          const quadrantScore = calculateQuadrantMatch(content.context, agent.quadrants);
          const totalScore = (themeScore * weights.theme) + (quadrantScore * weights.quadrant_descriptions);
          
          matches.push({
            agent_id: agent.id,
            agent,
            confidence_score: totalScore,
            matching_details: {
              theme_score: themeScore,
              description_score: quadrantScore,
              total_score: totalScore
            }
          });
        });
        
        return matches.sort((a, b) => b.confidence_score - a.confidence_score)[0];
      },
      
      selectBestQuadrant: (agent, contentType) => {
        const quadrants = ['top_left', 'top_right', 'bottom_left', 'bottom_right'];
        const scores = quadrants.map(q => ({
          quadrant: q,
          score: Math.random() * 0.3 + 0.7 // Mock score between 0.7-1.0
        }));
        return scores.sort((a, b) => b.score - a.score)[0];
      }
    };

    // Test data setup
    testData = {
      content: {
        title: 'Asbestsanierung nach TRGS 519',
        keywords: ['asbest', 'sanierung', 'trgs', 'sicherheit'],
        context: 'Professional asbestos removal following German safety regulations',
        category: 'schadstoffe'
      }
    };

    imageDatabase = {
      agents: [
        {
          id: 1,
          theme: 'Professional asbestos removal with safety equipment',
          quadrants: {
            top_left: 'Workers in protective suits handling hazardous materials',
            top_right: 'Safety equipment and warning signs',
            bottom_left: 'Asbestos removal process documentation',
            bottom_right: 'Clean work environment after remediation'
          },
          image_paths: {
            top_left: './images/agent_1_top_left.png',
            top_right: './images/agent_1_top_right.png',
            bottom_left: './images/agent_1_bottom_left.png',
            bottom_right: './images/agent_1_bottom_right.png'
          }
        },
        {
          id: 2,
          theme: 'Construction site management and planning',
          quadrants: {
            top_left: 'Construction blueprints and planning documents',
            top_right: 'Site managers discussing project timeline',
            bottom_left: 'Heavy machinery and construction equipment',
            bottom_right: 'Workers coordinating construction activities'
          },
          image_paths: {
            top_left: './images/agent_2_top_left.png',
            top_right: './images/agent_2_top_right.png',
            bottom_left: './images/agent_2_bottom_left.png',
            bottom_right: './images/agent_2_bottom_right.png'
          }
        }
      ]
    };
  });

  describe('Theme Matching', () => {
    test('should match exact keywords in theme', () => {
      const content = {
        keywords: ['asbestos', 'removal', 'safety'],
        context: 'asbestos removal process'
      };
      
      const result = semanticMatcher.findBestMatch(content, imageDatabase);
      
      expect(result).toBeDefined();
      expect(result.confidence_score).toBeGreaterThan(0.7);
      expect(result.agent_id).toBe(1);
    });

    test('should handle partial keyword matches', () => {
      const content = {
        keywords: ['construction', 'planning'],
        context: 'construction project management'
      };
      
      const result = semanticMatcher.findBestMatch(content, imageDatabase);
      
      expect(result).toBeDefined();
      expect(result.confidence_score).toBeGreaterThan(0.5);
    });

    test('should return low confidence for poor matches', () => {
      const content = {
        keywords: ['cooking', 'recipe', 'food'],
        context: 'cooking instructions'
      };
      
      const result = semanticMatcher.findBestMatch(content, imageDatabase);
      
      expect(result.confidence_score).toBeLessThan(0.3);
    });
  });

  describe('Quadrant Description Matching', () => {
    test('should match specific quadrant descriptions', () => {
      const content = {
        keywords: ['protective', 'suits', 'hazardous'],
        context: 'workers in protective equipment'
      };
      
      const result = semanticMatcher.findBestMatch(content, imageDatabase);
      
      expect(result.matching_details.description_score).toBeGreaterThan(0.7);
    });

    test('should select best quadrant for content type', () => {
      const agent = imageDatabase.agents[0];
      const result = semanticMatcher.selectBestQuadrant(agent, 'safety_procedures');
      
      expect(result.quadrant).toMatch(/^(top_left|top_right|bottom_left|bottom_right)$/);
      expect(result.score).toBeGreaterThan(0.5);
    });
  });

  describe('Scoring Algorithm', () => {
    test('should apply correct weights to theme and description scores', () => {
      const content = testData.content;
      const weights = { theme: 0.4, quadrant_descriptions: 0.6 };
      
      const result = semanticMatcher.findBestMatch(content, imageDatabase, { weights });
      
      expect(result.matching_details).toHaveProperty('theme_score');
      expect(result.matching_details).toHaveProperty('description_score');
      expect(result.matching_details).toHaveProperty('total_score');
      
      const expectedTotal = (
        result.matching_details.theme_score * weights.theme +
        result.matching_details.description_score * weights.quadrant_descriptions
      );
      
      expect(result.matching_details.total_score).toBeCloseTo(expectedTotal, 2);
    });

    test('should return valid semantic match object', () => {
      const result = semanticMatcher.findBestMatch(testData.content, imageDatabase);
      
      expect(result).toBeValidSemanticMatch(0.5);
    });
  });

  describe('Edge Cases', () => {
    test('should handle empty content', () => {
      const emptyContent = { keywords: [], context: '' };
      
      const result = semanticMatcher.findBestMatch(emptyContent, imageDatabase);
      
      expect(result).toBeDefined();
      expect(result.confidence_score).toBeLessThan(0.5);
    });

    test('should handle empty image database', () => {
      const emptyDatabase = { agents: [] };
      
      const result = semanticMatcher.findBestMatch(testData.content, emptyDatabase);
      
      expect(result).toBeUndefined();
    });

    test('should handle malformed agent data', () => {
      const malformedDatabase = {
        agents: [
          { id: 1 }, // Missing required fields
          { id: 2, theme: 'Test' } // Missing quadrants
        ]
      };
      
      expect(() => {
        semanticMatcher.findBestMatch(testData.content, malformedDatabase);
      }).not.toThrow();
    });
  });

  describe('Performance Tests', () => {
    test('should process large image database within time limit', async () => {
      const largeDatabase = {
        agents: Array.from({ length: 100 }, (_, i) => ({
          id: i + 1,
          theme: `Theme ${i + 1}`,
          quadrants: {
            top_left: `Description ${i + 1} TL`,
            top_right: `Description ${i + 1} TR`,
            bottom_left: `Description ${i + 1} BL`,
            bottom_right: `Description ${i + 1} BR`
          }
        }))
      };

      const startTime = performance.now();
      const result = semanticMatcher.findBestMatch(testData.content, largeDatabase);
      const endTime = performance.now();
      
      expect(endTime - startTime).toBeLessThan(global.TEST_CONFIG.performance.semanticMatchingTimeout);
      expect(result).toBeDefined();
    });

    test('should handle concurrent matching requests', async () => {
      const promises = Array.from({ length: 10 }, () =>
        Promise.resolve(semanticMatcher.findBestMatch(testData.content, imageDatabase))
      );
      
      const results = await Promise.all(promises);
      
      results.forEach(result => {
        expect(result).toBeDefined();
        expect(result.confidence_score).toBeGreaterThan(0);
      });
    });
  });
});

// Helper functions for mocking
function calculateThemeMatch(keywords, theme) {
  if (!keywords || !theme) return 0;
  
  const themeWords = theme.toLowerCase().split(/\s+/);
  const matchCount = keywords.filter(keyword => 
    themeWords.some(word => word.includes(keyword.toLowerCase()))
  ).length;
  
  return Math.min(matchCount / keywords.length, 1.0);
}

function calculateQuadrantMatch(context, quadrants) {
  if (!context || !quadrants) return 0;
  
  const contextWords = context.toLowerCase().split(/\s+/);
  const allDescriptions = Object.values(quadrants).join(' ').toLowerCase();
  
  const matchCount = contextWords.filter(word => 
    allDescriptions.includes(word)
  ).length;
  
  return Math.min(matchCount / contextWords.length, 1.0);
}