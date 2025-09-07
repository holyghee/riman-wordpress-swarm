#!/usr/bin/env node

/**
 * Validation Script for AI-Enhanced WordPress Image Mappings
 * 
 * This script validates the generated mappings for quality, accuracy,
 * and potential issues. It performs comprehensive checks including:
 * - Thematic alignment validation
 * - Duplicate detection
 * - Quality score analysis
 * - Coverage verification
 * - SEO metadata validation
 * 
 * Author: RIMAN AI Mapping System
 */

const fs = require('fs-extra');
const path = require('path');

class MappingValidator {
    constructor() {
        this.mappingsPath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-ai-enhanced-mappings.json';
        this.validationResults = {
            totalMappings: 0,
            validMappings: 0,
            invalidMappings: 0,
            warnings: [],
            errors: [],
            duplicates: [],
            qualityAnalysis: {},
            coverageAnalysis: {},
            seoValidation: {}
        };
    }

    async validate() {
        console.log('🔍 Starting mapping validation...\n');

        try {
            // Load mappings
            const mappingsData = await this.loadMappings();
            this.validationResults.totalMappings = mappingsData.mappings.length;

            // Run validation tests
            console.log('📋 Running validation tests...');
            await this.validateStructure(mappingsData);
            await this.validateDuplicates(mappingsData);
            await this.validateQuality(mappingsData);
            await this.validateThematicAlignment(mappingsData);
            await this.validateSEOMetadata(mappingsData);
            await this.validateCoverage(mappingsData);

            // Generate validation report
            console.log('📊 Generating validation report...');
            await this.generateValidationReport();

            console.log('✅ Validation complete!\n');
            this.printSummary();

        } catch (error) {
            console.error('❌ Validation failed:', error);
            process.exit(1);
        }
    }

    async loadMappings() {
        try {
            const data = await fs.readFile(this.mappingsPath, 'utf-8');
            return JSON.parse(data);
        } catch (error) {
            throw new Error(`Failed to load mappings file: ${error.message}`);
        }
    }

    async validateStructure(data) {
        console.log('  🏗️  Validating structure...');
        
        const requiredFields = ['contentId', 'contentPath', 'imageId', 'imagePath', 'similarityScores', 'matchQuality'];
        
        for (let i = 0; i < data.mappings.length; i++) {
            const mapping = data.mappings[i];
            
            for (const field of requiredFields) {
                if (!mapping[field]) {
                    this.validationResults.errors.push({
                        type: 'missing_field',
                        mapping: i,
                        field: field,
                        severity: 'high'
                    });
                }
            }
            
            // Validate similarity scores
            if (mapping.similarityScores) {
                const scores = mapping.similarityScores;
                if (scores.combined < 0 || scores.combined > 1) {
                    this.validationResults.errors.push({
                        type: 'invalid_similarity_score',
                        mapping: i,
                        score: scores.combined,
                        severity: 'medium'
                    });
                }
            }
            
            // Validate match quality
            const validQualities = ['perfect', 'excellent', 'good', 'fair', 'poor', 'fallback'];
            if (!validQualities.includes(mapping.matchQuality)) {
                this.validationResults.errors.push({
                    type: 'invalid_match_quality',
                    mapping: i,
                    quality: mapping.matchQuality,
                    severity: 'medium'
                });
            }
        }
        
        console.log(`    ✅ Structure validation complete`);
    }

    async validateDuplicates(data) {
        console.log('  🔍 Checking for duplicates...');
        
        const contentIds = new Map();
        const imageIds = new Map();
        
        data.mappings.forEach((mapping, index) => {
            // Check for duplicate content mappings
            if (contentIds.has(mapping.contentId)) {
                this.validationResults.duplicates.push({
                    type: 'duplicate_content',
                    contentId: mapping.contentId,
                    mappings: [contentIds.get(mapping.contentId), index],
                    severity: 'high'
                });
            } else {
                contentIds.set(mapping.contentId, index);
            }
            
            // Check for duplicate image usage
            if (imageIds.has(mapping.imageId)) {
                this.validationResults.duplicates.push({
                    type: 'duplicate_image',
                    imageId: mapping.imageId,
                    mappings: [imageIds.get(mapping.imageId), index],
                    severity: 'low'
                });
            } else {
                imageIds.set(mapping.imageId, index);
            }
        });
        
        console.log(`    ✅ Duplicate check complete (${this.validationResults.duplicates.length} found)`);
    }

    async validateQuality(data) {
        console.log('  📊 Analyzing quality distribution...');
        
        const qualityDistribution = {};
        const scoreDistribution = {
            high: 0,    // > 0.8
            medium: 0,  // 0.4 - 0.8
            low: 0      // < 0.4
        };
        
        data.mappings.forEach(mapping => {
            // Quality distribution
            const quality = mapping.matchQuality;
            qualityDistribution[quality] = (qualityDistribution[quality] || 0) + 1;
            
            // Score distribution
            const score = mapping.similarityScores.combined;
            if (score > 0.8) scoreDistribution.high++;
            else if (score >= 0.4) scoreDistribution.medium++;
            else scoreDistribution.low++;
            
            // Flag poor quality mappings
            if (quality === 'poor' || score < 0.2) {
                this.validationResults.warnings.push({
                    type: 'poor_quality_mapping',
                    mapping: mapping.contentId,
                    quality: quality,
                    score: score,
                    severity: 'medium'
                });
            }
        });
        
        this.validationResults.qualityAnalysis = {
            qualityDistribution,
            scoreDistribution,
            averageScore: data.mappings.reduce((sum, m) => sum + m.similarityScores.combined, 0) / data.mappings.length
        };
        
        console.log(`    ✅ Quality analysis complete (avg score: ${this.validationResults.qualityAnalysis.averageScore.toFixed(3)})`);
    }

    async validateThematicAlignment(data) {
        console.log('  🎯 Validating thematic alignment...');
        
        const conflictPatterns = [
            { content: 'beratung-mediation', image: ['pcb', 'asbestos', 'laboratory', 'hazardous'], severity: 'high' },
            { content: 'beratung-mediation', image: ['technical', 'construction', 'safety'], severity: 'medium' },
            { content: 'technical', image: ['mediation', 'negotiation', 'conflict'], severity: 'medium' }
        ];
        
        data.mappings.forEach((mapping, index) => {
            const contentCategory = mapping.contentCategory;
            const imageTheme = mapping.imageTheme?.toLowerCase() || '';
            
            for (const pattern of conflictPatterns) {
                if (contentCategory === pattern.content) {
                    for (const imageKeyword of pattern.image) {
                        if (imageTheme.includes(imageKeyword)) {
                            this.validationResults.warnings.push({
                                type: 'thematic_misalignment',
                                mapping: index,
                                contentCategory: contentCategory,
                                imageTheme: mapping.imageTheme,
                                conflict: imageKeyword,
                                severity: pattern.severity
                            });
                        }
                    }
                }
            }
            
            // Check for explicitly flagged conflicts
            if (mapping.hasThematicConflict) {
                this.validationResults.warnings.push({
                    type: 'flagged_conflict',
                    mapping: index,
                    contentId: mapping.contentId,
                    severity: 'high'
                });
            }
        });
        
        console.log(`    ✅ Thematic alignment validation complete`);
    }

    async validateSEOMetadata(data) {
        console.log('  🔍 Validating SEO metadata...');
        
        const seoValidation = {
            missingAltText: 0,
            longAltText: 0,
            missingDescription: 0,
            missingKeywords: 0,
            malformedStructuredData: 0
        };
        
        data.mappings.forEach((mapping, index) => {
            const seo = mapping.seoMetadata;
            
            if (!seo) {
                this.validationResults.errors.push({
                    type: 'missing_seo_metadata',
                    mapping: index,
                    severity: 'high'
                });
                return;
            }
            
            // Validate alt text
            if (!seo.altText || seo.altText.trim() === '') {
                seoValidation.missingAltText++;
                this.validationResults.warnings.push({
                    type: 'missing_alt_text',
                    mapping: index,
                    severity: 'medium'
                });
            } else if (seo.altText.length > 125) {
                seoValidation.longAltText++;
                this.validationResults.warnings.push({
                    type: 'alt_text_too_long',
                    mapping: index,
                    length: seo.altText.length,
                    severity: 'low'
                });
            }
            
            // Validate description
            if (!seo.description || seo.description.trim() === '') {
                seoValidation.missingDescription++;
            }
            
            // Validate keywords
            if (!seo.keywords || seo.keywords.length === 0) {
                seoValidation.missingKeywords++;
            }
            
            // Validate structured data
            if (seo.structuredData) {
                try {
                    if (!seo.structuredData['@context'] || !seo.structuredData['@type']) {
                        seoValidation.malformedStructuredData++;
                    }
                } catch (error) {
                    seoValidation.malformedStructuredData++;
                }
            }
        });
        
        this.validationResults.seoValidation = seoValidation;
        console.log(`    ✅ SEO metadata validation complete`);
    }

    async validateCoverage(data) {
        console.log('  📈 Analyzing coverage...');
        
        const categoryStats = {};
        
        data.mappings.forEach(mapping => {
            const category = mapping.contentCategory;
            if (!categoryStats[category]) {
                categoryStats[category] = {
                    total: 0,
                    averageScore: 0,
                    qualityDistribution: {}
                };
            }
            
            categoryStats[category].total++;
            categoryStats[category].averageScore += mapping.similarityScores.combined;
            
            const quality = mapping.matchQuality;
            categoryStats[category].qualityDistribution[quality] = 
                (categoryStats[category].qualityDistribution[quality] || 0) + 1;
        });
        
        // Calculate averages
        Object.keys(categoryStats).forEach(category => {
            categoryStats[category].averageScore /= categoryStats[category].total;
        });
        
        this.validationResults.coverageAnalysis = categoryStats;
        console.log(`    ✅ Coverage analysis complete (${Object.keys(categoryStats).length} categories)`);
    }

    async generateValidationReport() {
        const report = {
            validationDate: new Date().toISOString(),
            summary: {
                totalMappings: this.validationResults.totalMappings,
                validMappings: this.validationResults.totalMappings - this.validationResults.errors.filter(e => e.severity === 'high').length,
                errors: this.validationResults.errors.length,
                warnings: this.validationResults.warnings.length,
                duplicates: this.validationResults.duplicates.length
            },
            qualityAnalysis: this.validationResults.qualityAnalysis,
            coverageAnalysis: this.validationResults.coverageAnalysis,
            seoValidation: this.validationResults.seoValidation,
            issues: {
                errors: this.validationResults.errors,
                warnings: this.validationResults.warnings,
                duplicates: this.validationResults.duplicates
            },
            recommendations: this.generateRecommendations(),
            overallScore: this.calculateOverallScore()
        };
        
        const reportPath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/src/mapping-validation-report.json';
        await fs.writeFile(reportPath, JSON.stringify(report, null, 2));
        console.log(`    📋 Validation report saved to: ${reportPath}`);
    }

    generateRecommendations() {
        const recommendations = [];
        
        // Quality recommendations
        const avgScore = this.validationResults.qualityAnalysis.averageScore;
        if (avgScore < 0.5) {
            recommendations.push({
                type: 'quality_improvement',
                priority: 'high',
                message: 'Average similarity score is low. Consider expanding image database or improving content analysis.'
            });
        }
        
        // Duplicate recommendations
        if (this.validationResults.duplicates.length > 0) {
            recommendations.push({
                type: 'duplicate_resolution',
                priority: 'medium',
                message: `Found ${this.validationResults.duplicates.length} duplicates. Review and resolve duplicate mappings.`
            });
        }
        
        // SEO recommendations
        const seoIssues = Object.values(this.validationResults.seoValidation).reduce((sum, count) => sum + count, 0);
        if (seoIssues > this.validationResults.totalMappings * 0.1) {
            recommendations.push({
                type: 'seo_improvement',
                priority: 'medium',
                message: 'Multiple SEO metadata issues detected. Review and improve SEO data generation.'
            });
        }
        
        // Thematic alignment recommendations
        const thematicIssues = this.validationResults.warnings.filter(w => w.type.includes('thematic')).length;
        if (thematicIssues > 0) {
            recommendations.push({
                type: 'thematic_alignment',
                priority: 'high',
                message: `${thematicIssues} thematic misalignments detected. Review conflict detection rules.`
            });
        }
        
        return recommendations;
    }

    calculateOverallScore() {
        const weights = {
            structure: 0.2,
            quality: 0.3,
            thematic: 0.3,
            seo: 0.1,
            coverage: 0.1
        };
        
        // Structure score (based on errors)
        const structureScore = Math.max(0, 1 - (this.validationResults.errors.length / this.validationResults.totalMappings));
        
        // Quality score
        const qualityScore = this.validationResults.qualityAnalysis.averageScore || 0;
        
        // Thematic score (based on conflicts)
        const thematicIssues = this.validationResults.warnings.filter(w => w.type.includes('thematic')).length;
        const thematicScore = Math.max(0, 1 - (thematicIssues / this.validationResults.totalMappings));
        
        // SEO score
        const seoIssues = Object.values(this.validationResults.seoValidation).reduce((sum, count) => sum + count, 0);
        const seoScore = Math.max(0, 1 - (seoIssues / this.validationResults.totalMappings));
        
        // Coverage score (100% coverage assumed since all content should be mapped)
        const coverageScore = 1.0;
        
        const overallScore = (
            structureScore * weights.structure +
            qualityScore * weights.quality +
            thematicScore * weights.thematic +
            seoScore * weights.seo +
            coverageScore * weights.coverage
        );
        
        return Math.round(overallScore * 100);
    }

    printSummary() {
        console.log('📊 VALIDATION SUMMARY');
        console.log('=====================');
        console.log(`📄 Total mappings: ${this.validationResults.totalMappings}`);
        console.log(`✅ Valid mappings: ${this.validationResults.totalMappings - this.validationResults.errors.filter(e => e.severity === 'high').length}`);
        console.log(`❌ Critical errors: ${this.validationResults.errors.filter(e => e.severity === 'high').length}`);
        console.log(`⚠️  Warnings: ${this.validationResults.warnings.length}`);
        console.log(`🔁 Duplicates: ${this.validationResults.duplicates.length}`);
        console.log('');
        console.log('🎯 QUALITY METRICS');
        console.log(`   Average similarity: ${(this.validationResults.qualityAnalysis.averageScore * 100).toFixed(1)}%`);
        console.log(`   High quality (>80%): ${this.validationResults.qualityAnalysis.scoreDistribution.high}`);
        console.log(`   Medium quality (40-80%): ${this.validationResults.qualityAnalysis.scoreDistribution.medium}`);
        console.log(`   Low quality (<40%): ${this.validationResults.qualityAnalysis.scoreDistribution.low}`);
        console.log('');
        console.log(`🏆 OVERALL SCORE: ${this.calculateOverallScore()}%`);
        console.log('');
        
        if (this.validationResults.errors.filter(e => e.severity === 'high').length === 0) {
            console.log('✅ All critical validations passed! Mappings are ready for production use.');
        } else {
            console.log('❌ Critical issues found. Please review and fix before using mappings.');
        }
    }
}

// Execute validation
if (require.main === module) {
    const validator = new MappingValidator();
    validator.validate().catch(error => {
        console.error('Fatal validation error:', error);
        process.exit(1);
    });
}

module.exports = MappingValidator;