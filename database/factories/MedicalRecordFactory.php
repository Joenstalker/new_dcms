<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(1, 999),
        ];
    }

    public static function defaultChecklist(): array
    {
        return [
            ['name' => 'Diabetes Mellitus', 'description' => 'Patient has a known history of diabetes mellitus.', 'sort_order' => 10],
            ['name' => 'Hypertension', 'description' => 'Patient has elevated blood pressure or antihypertensive maintenance.', 'sort_order' => 20],
            ['name' => 'Coronary Artery Disease', 'description' => 'Patient reports ischemic heart disease or prior cardiac intervention.', 'sort_order' => 30],
            ['name' => 'Congestive Heart Failure', 'description' => 'Patient has reduced cardiac function requiring medical follow-up.', 'sort_order' => 40],
            ['name' => 'Cardiac Arrhythmia', 'description' => 'Patient has irregular heartbeat history (e.g., AF, SVT).', 'sort_order' => 50],
            ['name' => 'Previous Stroke', 'description' => 'Patient has prior cerebrovascular accident or TIA.', 'sort_order' => 60],
            ['name' => 'Asthma', 'description' => 'Patient has asthma or recurring breathing concerns.', 'sort_order' => 70],
            ['name' => 'Chronic Obstructive Pulmonary Disease', 'description' => 'Patient has chronic airway disease (COPD/emphysema/chronic bronchitis).', 'sort_order' => 80],
            ['name' => 'Tuberculosis History', 'description' => 'Patient has previous or active tuberculosis diagnosis.', 'sort_order' => 90],
            ['name' => 'Sleep Apnea', 'description' => 'Patient has diagnosed sleep apnea or uses CPAP.', 'sort_order' => 100],
            ['name' => 'Chronic Kidney Disease', 'description' => 'Patient has impaired renal function or dialysis history.', 'sort_order' => 110],
            ['name' => 'Liver Disease', 'description' => 'Patient reports hepatitis, cirrhosis, or chronic hepatic dysfunction.', 'sort_order' => 120],
            ['name' => 'Thyroid Disorder', 'description' => 'Patient has hypo/hyperthyroidism or thyroid treatment history.', 'sort_order' => 130],
            ['name' => 'Adrenal Disorder', 'description' => 'Patient has adrenal insufficiency or steroid-dependent condition.', 'sort_order' => 140],
            ['name' => 'Anemia', 'description' => 'Patient has clinically significant anemia history.', 'sort_order' => 150],
            ['name' => 'Bleeding Disorder', 'description' => 'Patient has coagulopathy (e.g., hemophilia, thrombocytopenia).', 'sort_order' => 160],
            ['name' => 'On Anticoagulant Therapy', 'description' => 'Patient is taking blood thinners (e.g., warfarin, apixaban).', 'sort_order' => 170],
            ['name' => 'On Antiplatelet Therapy', 'description' => 'Patient is taking antiplatelet medications (e.g., aspirin, clopidogrel).', 'sort_order' => 180],
            ['name' => 'History of Excessive Bleeding', 'description' => 'Patient reports prolonged bleeding after surgery/extractions.', 'sort_order' => 190],
            ['name' => 'Cancer History', 'description' => 'Patient has current or prior malignancy diagnosis.', 'sort_order' => 200],
            ['name' => 'Undergoing Chemotherapy', 'description' => 'Patient is currently receiving cytotoxic chemotherapy.', 'sort_order' => 210],
            ['name' => 'Undergoing Radiotherapy', 'description' => 'Patient is currently receiving radiation therapy.', 'sort_order' => 220],
            ['name' => 'Bisphosphonate Use', 'description' => 'Patient has oral/IV bisphosphonate or antiresorptive therapy history.', 'sort_order' => 230],
            ['name' => 'Immunocompromised State', 'description' => 'Patient has reduced immune function due to disease or treatment.', 'sort_order' => 240],
            ['name' => 'HIV/AIDS', 'description' => 'Patient reports HIV infection or AIDS diagnosis.', 'sort_order' => 250],
            ['name' => 'Epilepsy', 'description' => 'Patient has seizure disorder history.', 'sort_order' => 260],
            ['name' => 'Parkinson Disease', 'description' => 'Patient has Parkinsonism affecting movement or oral function.', 'sort_order' => 270],
            ['name' => 'Dementia/Cognitive Impairment', 'description' => 'Patient has memory or cognitive impairment impacting consent/compliance.', 'sort_order' => 280],
            ['name' => 'Psychiatric Disorder', 'description' => 'Patient has psychiatric condition requiring ongoing management.', 'sort_order' => 290],
            ['name' => 'Depression/Anxiety', 'description' => 'Patient has mood or anxiety disorder diagnosis.', 'sort_order' => 300],
            ['name' => 'Latex Allergy', 'description' => 'Patient has documented allergy to latex products.', 'sort_order' => 310],
            ['name' => 'Penicillin Allergy', 'description' => 'Patient has hypersensitivity reaction to penicillin-class antibiotics.', 'sort_order' => 320],
            ['name' => 'NSAID Allergy', 'description' => 'Patient reports adverse reaction to NSAIDs (ibuprofen, aspirin, etc.).', 'sort_order' => 330],
            ['name' => 'Local Anesthetic Allergy', 'description' => 'Patient has suspected or confirmed allergy to local anesthetics.', 'sort_order' => 340],
            ['name' => 'Other Drug Allergy', 'description' => 'Patient has other medication allergy requiring alert.', 'sort_order' => 350],
            ['name' => 'Food Allergy', 'description' => 'Patient has clinically relevant food allergy.', 'sort_order' => 360],
            ['name' => 'Smoking (Current)', 'description' => 'Patient currently smokes cigarettes/tobacco products.', 'sort_order' => 370],
            ['name' => 'Alcohol Use (Heavy)', 'description' => 'Patient reports heavy alcohol consumption.', 'sort_order' => 380],
            ['name' => 'Substance Use History', 'description' => 'Patient reports recreational or controlled substance misuse history.', 'sort_order' => 390],
            ['name' => 'Pregnancy', 'description' => 'Patient is currently pregnant.', 'sort_order' => 400],
            ['name' => 'Breastfeeding', 'description' => 'Patient is currently breastfeeding.', 'sort_order' => 410],
            ['name' => 'History of Infective Endocarditis', 'description' => 'Patient has prior infective endocarditis diagnosis.', 'sort_order' => 420],
            ['name' => 'Prosthetic Heart Valve', 'description' => 'Patient has prosthetic cardiac valve requiring prophylaxis review.', 'sort_order' => 430],
            ['name' => 'Joint Replacement', 'description' => 'Patient has prosthetic joint placement history.', 'sort_order' => 440],
            ['name' => 'Rheumatoid Arthritis', 'description' => 'Patient has autoimmune inflammatory joint disease.', 'sort_order' => 450],
            ['name' => 'Systemic Lupus Erythematosus', 'description' => 'Patient has SLE or related autoimmune connective tissue disease.', 'sort_order' => 460],
            ['name' => 'Gastroesophageal Reflux Disease', 'description' => 'Patient has GERD requiring medication or dietary control.', 'sort_order' => 470],
            ['name' => 'Peptic Ulcer Disease', 'description' => 'Patient has ulcer disease history relevant for NSAID prescribing.', 'sort_order' => 480],
            ['name' => 'Chronic Steroid Use', 'description' => 'Patient uses long-term corticosteroids.', 'sort_order' => 490],
            ['name' => 'Recent Major Surgery/Hospitalization', 'description' => 'Patient had recent major surgery or inpatient admission.', 'sort_order' => 500],
        ];
    }
}
