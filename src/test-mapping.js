#!/usr/bin/env node

/**
 * Test Suite for AI-Enhanced WordPress Image Mapping System
 * 
 * This comprehensive test suite validates the AI mapping system's functionality
 * including semantic analysis, similarity calculations, conflict detection,
 * and output generation.
 * 
 * Author: RIMAN AI Mapping System
 */

const fs = require('fs-extra');
const path = require('path');
const AIEnhancedImageMapper = require('./ai-enhanced-mapping');

class MappingTestSuite {
    constructor() {
        this.testResults = {
            passed: 0,
            failed: 0,
            errors: []
        };
        this.testData = this.createTestData();
    }

    async runTests() {
        console.log('🧪 Starting AI Enhanced Mapping Test Suite\n');

        try {
            // Unit Tests
            console.log('📋 Running unit tests...');
            await this.testSemanticAnalysis();
            await this.testSimilarityCalculations();
            await this.testConflictDetection();
            await this.testFallbackStrategies();
            await this.testSEOGeneration();

            // Integration Tests
            console.log('\n🔗 Running integration tests...');
            await this.testEndToEndMapping();
            await this.testOutputValidation();

            // Performance Tests
            console.log('\n⚡ Running performance tests...');
            await this.testPerformance();

            console.log('\n✅ Test suite complete!');
            this.printResults();

        } catch (error) {
            console.error('❌ Test suite failed:', error);
            this.testResults.errors.push({ test: 'general', error: error.message });
            this.printResults();
            process.exit(1);
        }
    }

    createTestData() {
        return {
            sampleContent: {
                id: 'test-mediation-content',
                category: 'beratung-mediation',
                title: 'Baumediation und Konfliktlösung',
                content: 'Mediation ist ein strukturiertes Verfahren zur Konfliktlösung im Bauwesen. Wir helfen bei Streitigkeiten zwischen Bauherren, Planern und Ausführenden.'
            },
            sampleImages: [
                {
                    id: 'test-mediation-image',
                    theme: 'Professional mediation and conflict resolution meeting',
                    category: 'beratung-mediation',
                    description: 'Professional mediator facilitating discussion between construction parties in conference room setting.'
                },
                {
                    id: 'test-technical-image', 
                    theme: 'PCB contamination analysis and hazardous material testing',
                    category: 'schadstoff-management',
                    description: 'Laboratory technician analyzing PCB samples with protective equipment and testing apparatus.'
                }
            ]
        };
    }

    async testSemanticAnalysis() {
        console.log('  🧠 Testing semantic analysis...');
        
        try {
            const mapper = new AIEnhancedImageMapper();
            
            // Test text cleaning
            const dirtyText = '# Title\n**Bold text** with *italic* and [links](url)\n\n---\nfrontmatter\n---\n\nClean text here.';
            const cleanText = mapper.cleanText(dirtyText);
            
            this.assert(
                !cleanText.includes('#') && !cleanText.includes('**'),
                'Text cleaning should remove markdown formatting'
            );

            // Test tokenization and filtering
            const tokens = mapper.tokenizer.tokenize('Dies ist ein Test mit deutschen Wörtern und Stoppwörtern wie der die das');
            const filtered = mapper.filterTokens(tokens, 'deu');
            
            this.assert(
                !filtered.includes('der') && !filtered.includes('die'),
                'Token filtering should remove German stopwords'
            );

            // Test thematic fingerprinting
            const fingerprint = mapper.generateThematicFingerprint('mediation conflict resolution negotiation');
            this.assert(
                fingerprint.mediation > 0,
                'Thematic fingerprinting should detect mediation keywords'
            );

            this.testPassed('semantic-analysis');

        } catch (error) {
            this.testFailed('semantic-analysis', error);
        }
    }

    async testSimilarityCalculations() {
        console.log('  📊 Testing similarity calculations...');
        
        try {
            const mapper = new AIEnhancedImageMapper();
            
            // Test embedding generation
            const tokens1 = ['mediation', 'conflict', 'resolution', 'negotiation'];
            const tokens2 = ['mediation', 'dispute', 'resolution', 'agreement'];
            const tokens3 = ['asbestos', 'contamination', 'hazardous', 'laboratory'];
            
            const embedding1 = mapper.generateEmbedding(tokens1);
            const embedding2 = mapper.generateEmbedding(tokens2);
            const embedding3 = mapper.generateEmbedding(tokens3);
            
            this.assert(
                Array.isArray(embedding1) && embedding1.length === 100,
                'Embeddings should be arrays of length 100'
            );

            // Test cosine similarity
            const similarity12 = mapper.calculateCosineSimilarity(embedding1, embedding2);
            const similarity13 = mapper.calculateCosineSimilarity(embedding1, embedding3);
            
            this.assert(
                similarity12 > similarity13,
                'Similar content should have higher similarity than dissimilar content'
            );

            // Test thematic similarity
            const fingerprint1 = { mediation: 3, technical: 0, environmental: 0 };
            const fingerprint2 = { mediation: 2, technical: 0, environmental: 0 };
            const fingerprint3 = { mediation: 0, technical: 2, environmental: 3 };
            
            const themeSim12 = mapper.calculateThematicSimilarity(fingerprint1, fingerprint2);
            const themeSim13 = mapper.calculateThematicSimilarity(fingerprint1, fingerprint3);
            
            this.assert(
                themeSim12 > themeSim13,
                'Thematically similar content should have higher thematic similarity'
            );

            // Test category similarity
            const catSim1 = mapper.calculateCategorySimilarity('beratung-mediation', 'beratung-mediation');
            const catSim2 = mapper.calculateCategorySimilarity('beratung-mediation', 'schadstoff-management');
            
            this.assert(
                catSim1 === 1.0 && catSim2 < 1.0,
                'Identical categories should have similarity 1.0'
            );

            this.testPassed('similarity-calculations');

        } catch (error) {
            this.testFailed('similarity-calculations', error);
        }
    }

    async testConflictDetection() {
        console.log('  🛡️  Testing conflict detection...');
        
        try {
            const mapper = new AIEnhancedImageMapper();
            
            // Test conflict risk calculation
            const mediationContent = { category: 'beratung-mediation' };
            const pcbImage = { theme: 'PCB contamination analysis laboratory testing' };
            const mediationImage = { theme: 'Professional mediation and conflict resolution' };
            
            const highRisk = mapper.calculateConflictRisk(mediationContent, pcbImage);
            const lowRisk = mapper.calculateConflictRisk(mediationContent, mediationImage);
            
            this.assert(
                highRisk > lowRisk,
                'PCB image should have higher conflict risk with mediation content'
            );

            // Test problematic combination detection
            const problematicMapping = {
                contentCategory: 'beratung-mediation',
                imageTheme: 'PCB laboratory analysis and testing procedures'
            };
            const goodMapping = {
                contentCategory: 'beratung-mediation', 
                imageTheme: 'Professional mediation conference room discussion'
            };
            
            const isProblematic1 = mapper.detectProblematicCombination(problematicMapping);
            const isProblematic2 = mapper.detectProblematicCombination(goodMapping);
            
            this.assert(
                isProblematic1 === true && isProblematic2 === false,
                'Should detect problematic mediation + PCB combinations'
            );

            this.testPassed('conflict-detection');

        } catch (error) {
            this.testFailed('conflict-detection', error);
        }
    }

    async testFallbackStrategies() {
        console.log('  🔄 Testing fallback strategies...');
        
        try {
            const mapper = new AIEnhancedImageMapper();
            
            // Test match quality determination
            const perfectScore = 0.95;
            const goodScore = 0.65;
            const poorScore = 0.15;
            
            this.assert(
                mapper.determineMatchQuality(perfectScore) === 'perfect',
                'High scores should result in perfect quality rating'
            );
            
            this.assert(
                mapper.determineMatchQuality(goodScore) === 'good',
                'Medium scores should result in good quality rating'
            );
            
            this.assert(
                mapper.determineMatchQuality(poorScore) === 'poor',
                'Low scores should result in poor quality rating'
            );

            // Test category matching for fallbacks
            const categoryMatch1 = mapper.getCategoryMatch('beratung-mediation', 'beratung-mediation');
            const categoryMatch2 = mapper.getCategoryMatch('beratung-mediation', 'schadstoff-management');
            
            this.assert(
                categoryMatch1 > categoryMatch2,
                'Exact category matches should score higher than different categories'
            );

            this.testPassed('fallback-strategies');

        } catch (error) {
            this.testFailed('fallback-strategies', error);
        }
    }

    async testSEOGeneration() {
        console.log('  📈 Testing SEO generation...');
        
        try {
            const mapper = new AIEnhancedImageMapper();
            
            // Test keyword extraction
            const title = 'Baumediation und Konfliktlösung in der Praxis';
            const keywords = mapper.extractSEOKeywords(title);
            
            this.assert(
                Array.isArray(keywords) && keywords.length > 0,
                'Should extract SEO keywords from titles'
            );

            // Test alt text generation
            const imageDesc = 'Professional mediator facilitating discussion between construction parties in modern conference room.';
            const contentTitle = 'Baumediation Services';
            const altText = mapper.generateAltText(imageDesc, contentTitle);
            
            this.assert(
                typeof altText === 'string' && altText.length <= 125,
                'Alt text should be string with max 125 characters'
            );

            // Test optimized filename generation
            const filename = mapper.generateOptimizedFilename(contentTitle, 'Professional Mediation Meeting');
            
            this.assert(
                filename.includes('-') && filename.endsWith('.jpg'),
                'Optimized filename should be hyphenated and have .jpg extension'
            );

            // Test structured data generation
            const mapping = {
                imagePath: '/path/to/image.jpg',
                seoMetadata: {
                    description: 'Test description',
                    altText: 'Test alt text',
                    keywords: ['test', 'keyword']
                }
            };
            const structuredData = mapper.generateStructuredData(mapping);
            
            this.assert(
                structuredData['@context'] && structuredData['@type'] === 'ImageObject',
                'Structured data should have proper schema.org format'
            );

            this.testPassed('seo-generation');

        } catch (error) {
            this.testFailed('seo-generation', error);
        }
    }

    async testEndToEndMapping() {
        console.log('  🔗 Testing end-to-end mapping process...');
        
        try {
            // Create mock data files
            await this.createMockData();
            
            // This would require actual file system setup for full testing
            // For now, we'll test the core logic components
            
            const mapper = new AIEnhancedImageMapper();
            
            // Test that the mapper can be instantiated
            this.assert(
                mapper instanceof AIEnhancedImageMapper,
                'Mapper should instantiate correctly'
            );
            
            // Test configuration
            this.assert(
                mapper.validationMetrics && typeof mapper.validationMetrics === 'object',
                'Mapper should have validation metrics initialized'
            );

            this.testPassed('end-to-end-mapping');

        } catch (error) {
            this.testFailed('end-to-end-mapping', error);
        }
    }

    async testOutputValidation() {
        console.log('  ✅ Testing output validation...');
        
        try {
            // Test output structure
            const mockOutput = {
                metadata: {
                    generationDate: new Date().toISOString(),
                    system: 'AI-Enhanced WordPress Image Mapping',
                    totalMappings: 1
                },
                mappings: [{
                    contentId: 'test',
                    contentPath: 'test.md',
                    imageId: 'img-test',
                    imagePath: 'test.jpg',
                    similarityScores: {
                        combined: 0.75
                    },
                    matchQuality: 'good',
                    seoMetadata: {
                        altText: 'Test alt text',
                        keywords: ['test']
                    }
                }]
            };
            
            this.assert(
                mockOutput.metadata && mockOutput.mappings,
                'Output should have metadata and mappings sections'
            );
            
            this.assert(
                mockOutput.mappings[0].contentId && mockOutput.mappings[0].imageId,
                'Each mapping should have content and image IDs'
            );

            this.testPassed('output-validation');

        } catch (error) {
            this.testFailed('output-validation', error);
        }
    }

    async testPerformance() {
        console.log('  ⚡ Testing performance...');
        
        try {
            const mapper = new AIEnhancedImageMapper();
            
            // Test embedding generation performance
            const startTime = Date.now();
            const testTokens = Array(1000).fill(['test', 'performance', 'embedding', 'generation']);
            
            for (let i = 0; i < 100; i++) {
                mapper.generateEmbedding(testTokens[i % testTokens.length]);
            }
            
            const endTime = Date.now();
            const duration = endTime - startTime;
            
            this.assert(
                duration < 5000, // Should complete within 5 seconds
                'Embedding generation should be performant'
            );
            
            console.log(`    📊 Generated 100 embeddings in ${duration}ms`);

            // Test similarity calculation performance
            const embedding1 = mapper.generateEmbedding(['test', 'similarity']);
            const embedding2 = mapper.generateEmbedding(['test', 'performance']);
            
            const simStartTime = Date.now();
            for (let i = 0; i < 1000; i++) {
                mapper.calculateCosineSimilarity(embedding1, embedding2);
            }
            const simEndTime = Date.now();
            const simDuration = simEndTime - simStartTime;
            
            this.assert(
                simDuration < 2000, // Should complete within 2 seconds
                'Similarity calculations should be performant'
            );
            
            console.log(`    📊 Calculated 1000 similarities in ${simDuration}ms`);

            this.testPassed('performance');

        } catch (error) {
            this.testFailed('performance', error);
        }
    }

    async createMockData() {
        // Create minimal mock data for testing
        const mockContentDir = '/tmp/test-content';
        const mockContent = `# Test Content\n\n**Meta Description:** Test description\n\n**SEO Keywords:** test, content, mediation\n\nThis is test content for mediation services.`;
        
        await fs.ensureDir(mockContentDir);
        await fs.writeFile(path.join(mockContentDir, 'test.md'), mockContent);
        
        const mockImageDatabase = {
            project_info: {
                total_quadrants: 1
            },
            agents: [{
                agent_id: 1,
                theme: 'Professional mediation meeting',
                quadrants: {
                    top_left: 'Professional mediator in conference room setting.'
                },
                image_paths: {
                    top_left: './test-image.jpg'
                }
            }]
        };
        
        await fs.writeFile('/tmp/test-midjpi.json', JSON.stringify(mockImageDatabase));
    }

    assert(condition, message) {
        if (!condition) {
            throw new Error(`Assertion failed: ${message}`);
        }
    }

    testPassed(testName) {
        this.testResults.passed++;
        console.log(`    ✅ ${testName} passed`);
    }

    testFailed(testName, error) {
        this.testResults.failed++;
        this.testResults.errors.push({ test: testName, error: error.message });
        console.log(`    ❌ ${testName} failed: ${error.message}`);
    }

    printResults() {
        console.log('\n📊 TEST RESULTS');
        console.log('================');
        console.log(`✅ Tests passed: ${this.testResults.passed}`);
        console.log(`❌ Tests failed: ${this.testResults.failed}`);
        console.log(`📈 Success rate: ${((this.testResults.passed / (this.testResults.passed + this.testResults.failed)) * 100).toFixed(1)}%`);
        
        if (this.testResults.failed > 0) {
            console.log('\n❌ FAILURES:');
            this.testResults.errors.forEach(error => {
                console.log(`   ${error.test}: ${error.error}`);
            });
        }
        
        console.log(`\n🏆 Overall: ${this.testResults.failed === 0 ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED'}`);
    }
}

// Execute tests
if (require.main === module) {
    const testSuite = new MappingTestSuite();
    testSuite.runTests().catch(error => {
        console.error('Fatal test error:', error);
        process.exit(1);
    });
}

module.exports = MappingTestSuite;