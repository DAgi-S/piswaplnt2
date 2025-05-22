# Production Analysis Framework

## Key Performance Indicators (KPIs)

### Efficiency Metrics
- **Overall Equipment Effectiveness (OEE)**
  - Calculation: OEE = Availability × Performance × Quality
  - Target: >85%
  - Purpose: Measures overall production efficiency

- **Production Rate**
  - Calculation: Units produced / Production time
  - Target: Varies by product
  - Purpose: Measures production speed

- **First Pass Yield**
  - Calculation: (Units produced without rework / Total units produced) × 100%
  - Target: >95%
  - Purpose: Measures production quality on first attempt

- **Machine Utilization**
  - Calculation: (Actual production time / Available production time) × 100%
  - Target: >80%
  - Purpose: Measures effective use of machinery

### Cost Metrics
- **Cost Per Unit (CPU)**
  - Calculation: Total production cost / Number of units produced
  - Target: Below standard cost
  - Purpose: Measures cost efficiency

- **Material Usage Variance**
  - Calculation: (Actual material used - Standard material) × Standard cost
  - Target: Minimal variance
  - Purpose: Tracks material waste and efficiency

- **Labor Efficiency**
  - Calculation: (Standard labor hours / Actual labor hours) × 100%
  - Target: >90%
  - Purpose: Measures workforce productivity

- **Overhead Allocation**
  - Calculation: Total overhead costs / Production output
  - Target: Below budgeted allocation
  - Purpose: Tracks indirect costs

### Quality Metrics
- **Defect Rate**
  - Calculation: (Defective units / Total units produced) × 100%
  - Target: <2%
  - Purpose: Measures quality issues

- **Scrap Rate**
  - Calculation: (Scrapped material value / Total material value) × 100%
  - Target: <3%
  - Purpose: Measures material waste

- **Customer Complaints**
  - Calculation: Number of complaints / Total units sold
  - Target: <0.1%
  - Purpose: Measures customer satisfaction with product quality

### Inventory Metrics
- **Raw Material Turnover**
  - Calculation: Cost of materials consumed / Average raw material inventory
  - Target: >12 (monthly)
  - Purpose: Measures inventory efficiency

- **Finished Goods Turnover**
  - Calculation: Cost of goods sold / Average finished goods inventory
  - Target: >24 (monthly)
  - Purpose: Measures inventory management efficiency

- **Days of Supply**
  - Calculation: Inventory value / (Annual COGS / 365)
  - Target: <30 days
  - Purpose: Measures inventory levels relative to usage

## Analysis Reports

### Daily Production Reports
- **Purpose**: Provide quick operational insights for production supervisors
- **Content**:
  - Production output vs. target
  - Quality issues
  - Machine downtime
  - Material shortages
  - Labor attendance
- **Format**: One-page dashboard with key metrics

### Weekly Performance Analysis
- **Purpose**: Analyze short-term trends for production managers
- **Content**:
  - Weekly KPI summary with comparisons to targets
  - Production bottlenecks identified
  - Quality issues analysis
  - Progress on open production orders
  - Upcoming production schedule
- **Format**: Detailed report with charts and recommendations

### Monthly Management Report
- **Purpose**: Provide strategic overview for upper management
- **Content**:
  - All KPIs with month-over-month comparisons
  - Cost variance analysis
  - Capacity utilization
  - Quality trend analysis
  - Inventory health
  - Recommendations for improvement
- **Format**: Comprehensive report with executive summary

### Quarterly Strategic Review
- **Purpose**: Assess long-term performance trends and strategic alignment
- **Content**:
  - Quarterly performance vs. annual targets
  - Cost reduction initiatives progress
  - Product quality trends
  - Capacity planning
  - Process improvement projects
- **Format**: Presentation with detailed backup data

## SQL Queries for Analysis

### Production Efficiency Analysis
```sql
SELECT 
    p.product_code,
    p.name AS product_name,
    COUNT(po.id) AS total_orders,
    AVG(po.completed_quantity / po.target_quantity * 100) AS completion_percentage,
    SUM(po.target_quantity) AS total_planned,
    SUM(po.completed_quantity) AS total_completed,
    AVG(DATEDIFF(po.actual_completion_date, po.start_date)) AS avg_production_days
FROM 
    production_orders po
JOIN 
    production_products p ON po.product_id = p.id
WHERE 
    po.status = 'completed'
    AND po.start_date BETWEEN ? AND ?
GROUP BY 
    po.product_id
ORDER BY 
    completion_percentage DESC;
```

### Material Usage Analysis
```sql
SELECT 
    rm.material_code,
    rm.name AS material_name,
    SUM(pom.required_quantity) AS total_required,
    SUM(pom.consumed_quantity) AS total_consumed,
    ROUND((SUM(pom.consumed_quantity) - SUM(pom.required_quantity)) / SUM(pom.required_quantity) * 100, 2) AS usage_variance_percent,
    COUNT(DISTINCT pom.production_order_id) AS order_count
FROM 
    production_order_materials pom
JOIN 
    raw_materials rm ON pom.material_id = rm.id
JOIN 
    production_orders po ON pom.production_order_id = po.id
WHERE 
    po.status = 'completed'
    AND po.actual_completion_date BETWEEN ? AND ?
GROUP BY 
    pom.material_id
HAVING 
    usage_variance_percent > 5
ORDER BY 
    usage_variance_percent DESC;
```

### Quality Analysis
```sql
SELECT 
    p.product_code,
    p.name AS product_name,
    COUNT(pqc.id) AS total_inspections,
    SUM(CASE WHEN pqc.status = 'passed' THEN 1 ELSE 0 END) AS passed_inspections,
    SUM(CASE WHEN pqc.status = 'failed' THEN 1 ELSE 0 END) AS failed_inspections,
    ROUND(SUM(CASE WHEN pqc.status = 'passed' THEN 1 ELSE 0 END) / COUNT(pqc.id) * 100, 2) AS pass_rate,
    SUM(pqc.quantity_checked) AS total_quantity_checked
FROM 
    production_quality_checks pqc
JOIN 
    production_orders po ON pqc.production_order_id = po.id
JOIN 
    production_products p ON po.product_id = p.id
WHERE 
    pqc.inspection_date BETWEEN ? AND ?
GROUP BY 
    po.product_id
ORDER BY 
    pass_rate;
```

### Cost Analysis
```sql
SELECT 
    p.product_code,
    p.name AS product_name,
    po.order_number,
    po.target_quantity,
    po.completed_quantity,
    -- Material Cost
    SUM(rm.cost_per_unit * pom.consumed_quantity) AS total_material_cost,
    -- Cost Per Unit
    ROUND(SUM(rm.cost_per_unit * pom.consumed_quantity) / po.completed_quantity, 2) AS material_cost_per_unit,
    -- Standard Cost
    p.production_cost AS standard_cost_per_unit,
    -- Variance
    ROUND((SUM(rm.cost_per_unit * pom.consumed_quantity) / po.completed_quantity) - p.production_cost, 2) AS cost_variance_per_unit,
    -- Variance Percentage
    ROUND(((SUM(rm.cost_per_unit * pom.consumed_quantity) / po.completed_quantity) - p.production_cost) / p.production_cost * 100, 2) AS cost_variance_percent
FROM 
    production_orders po
JOIN 
    production_products p ON po.product_id = p.id
JOIN 
    production_order_materials pom ON po.id = pom.production_order_id
JOIN 
    raw_materials rm ON pom.material_id = rm.id
WHERE 
    po.status = 'completed'
    AND po.actual_completion_date BETWEEN ? AND ?
GROUP BY 
    po.id
HAVING 
    cost_variance_percent > 5
ORDER BY 
    cost_variance_percent DESC;
```

## Visualization Dashboards

### Production Dashboard
- **Daily Production Tracker**
  - Real-time display of daily production targets vs. actual
  - Hourly production rate
  - Current shift performance

- **Equipment Status**
  - Machine availability
  - Utilization rate
  - Downtime reasons

- **Quality Control**
  - Real-time defect rates
  - Quality inspection results
  - Top defect types

### Management Dashboard
- **KPI Overview**
  - Traffic light indicators for all KPIs
  - Trend charts showing performance over time
  - Drill-down capabilities for problem areas

- **Cost Analysis**
  - Cost per unit trends
  - Material variance analysis
  - Labor efficiency

- **Inventory Status**
  - Current inventory levels
  - Materials below safety stock
  - Inventory aging analysis

## Continuous Improvement Methods

### Statistical Process Control (SPC)
- **Control Charts**: Monitor production processes to identify special cause variations
- **Process Capability Analysis**: Determine if processes can consistently meet specifications
- **Root Cause Analysis**: Use tools like 5-Why and Ishikawa diagrams to identify underlying issues

### Lean Manufacturing Techniques
- **Value Stream Mapping**: Identify waste in the production process
- **5S Methodology**: Sort, Set in order, Shine, Standardize, Sustain
- **Kaizen Events**: Focused improvement projects to eliminate waste

### Six Sigma Methods
- **DMAIC Methodology**: Define, Measure, Analyze, Improve, Control
- **Measurement System Analysis**: Ensure data collection systems are accurate
- **Design of Experiments**: Systematically test process improvements

## Implementation Guidelines

1. **Prioritize Key Metrics**
   - Start with 3-5 critical KPIs aligned with business goals
   - Establish clear targets and review cadence

2. **Data Collection Infrastructure**
   - Ensure production data is captured accurately at source
   - Implement real-time data collection where possible
   - Validate data integrity regularly

3. **Analysis Cadence**
   - Daily operational reviews (15-30 minutes)
   - Weekly performance reviews (1-2 hours)
   - Monthly strategic reviews (2-4 hours)

4. **Action Planning Process**
   - Create clear accountability for metric performance
   - Implement formal corrective action process
   - Track improvement initiatives to completion

5. **Continuous Refinement**
   - Review metrics quarterly to ensure relevance
   - Add or modify metrics as production systems mature
   - Benchmark against industry standards 