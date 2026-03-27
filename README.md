---

## Mathematical & Strategic Rationale

### The Bayesian Paradigm: Beyond Point Estimates
Most pricing systems rely on frequentist moving averages, which suffer from high variance and "noise" in low-sample environments. This engine shifts the paradigm to **Probabilistic Decision Theory**. Instead of asking "What is the conversion rate?", the system asks "What is our current belief distribution about the conversion rate?"

#### 1. Conjugate Priors & The Beta-Binomial Model
The core of the engine utilizes the **Beta distribution** as a conjugate prior for the Bernoulli process (conversions vs. bounces). This allows for recursive, $O(1)$ complexity updates without needing to re-process the entire historical dataset:

* **Alpha ($\alpha$):** Represents successful conversions (plus a Laplace smoothing factor).
* **Beta ($\beta$):** Represents non-conversions.
* **Update Rule:** $\alpha_{new} = \alpha_{old} + 1$ (for conversion) or $\beta_{new} = \beta_{old} + 1$ (for bounce).

This mathematical choice ensures the model is **computationally efficient** and **temporally consistent**, making it suitable for high-throughput market ingestion.

#### 2. Managing Epistemic Uncertainty
Traditional systems often "overfit" to early data. By tracking the variance of the Beta distribution, this engine explicitly quantifies **Epistemic Uncertainty** (uncertainty due to lack of knowledge):

* **Narrow Distribution:** High confidence; the engine switches to *Exploitation* (maximizing revenue).
* **Wide Distribution:** Low confidence; the engine signals for more *Exploration* (gathering data).

#### 3. Expected Revenue Optimization (The Multi-Armed Bandit)
The "Winning Price" isn't simply the one with the highest conversion rate. The engine calculates the **Expected Value of Revenue ($E[R]$)** by integrating the conversion probability across the price point:

$$E[R] = Price \times \frac{\alpha}{\alpha + \beta}$$

This enables the system to identify high-margin, lower-conversion "sweet spots" that standard frequentist models would overlook.

#### 4. High-Precision Implementation
To prevent the catastrophic loss of precision inherent in binary floating-point arithmetic (IEEE 754), all probability density calculations are handled via `brick/math` using **arbitrary-precision decimals**. These are persisted as `DECIMAL(30,20)` in PostgreSQL, ensuring that the belief state remains mathematically exact even after millions of recursive updates.

---

### 🛠 Project Architecture Recap
* **Backend:** PHP 8.4 / Laravel 12 (Domain-Driven Design).
* **Inference Loop:** Asynchronous Event-Driven updates via Redis Streams.
* **Frontend:** React 19 / TypeScript (Real-time distribution visualization).
* **Infrastructure:** Dockerized Microservices (Nginx, PHP-FPM, Postgres 17, Redis).

---