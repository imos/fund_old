#include <math.h>
#include <stdint.h>
#include <stdio.h>
#include <stdlib.h>
#include <algorithm>
#include <vector>
using namespace std;

typedef int64_t int64;

unsigned long RandomInteger(){ 
  static unsigned long x = 123456789;
  static unsigned long y = 362436069;
  static unsigned long z = 521288629;
  static unsigned long w = 88675123;
  unsigned long t = x ^ (x << 11);
  x = y;
  y = z;
  z = w;
  return w = (w ^ (w >> 19)) ^ (t ^ (t >> 8)); 
}

int64 GetEnvironmentInteger(const char* name, int64 default_value) {
  const char* value = getenv(name);
  int64 result;
  if (value != nullptr && sscanf(value, "%lld", &result) > 0) {
    return result;
  }
  return default_value;
}

class Bootstrap {
 public:
  const double kBucketRange = 0.01;

  Bootstrap()
      : buckets_(GetEnvironmentInteger("BOOTSTRAP_BUCKET_SIZE", 200000)),
        trial_(GetEnvironmentInteger("BOOTSTRAP_TRIAL", 1000000LL)),
        leap_(GetEnvironmentInteger("BOOTSTRAP_LEAP", 10)) {}

  void Calculate(const vector<double> growth_rates) {
    vector<double> growth_accumulated_rates(GetAccumulatedRates(growth_rates));
    vector<double> growth_queue(growth_rates.size() / leap_);
    const int trial_count = trial_ / growth_rates.size();
    double growth_rate_sum = 0.0;
    for (int trial = 0; trial < trial_count; trial++) {
      for (int index = 0; index < growth_queue.size(); index++) {
        int position = RandomInteger() % (growth_rates.size() - leap_ + 1);
        double growth_rate = (growth_accumulated_rates[position + leap_] -
                              (position == 0 ? 0.0 :
                                   growth_accumulated_rates[position - 1])) /
                             leap_;
        growth_rate_sum -= growth_queue[index];
        growth_queue[index] = growth_rate;
        growth_rate_sum += growth_rate;
        if (trial != 0) {
          IncrementBucket(growth_rate_sum / growth_queue.size());
        }
      }
    }
  }

  void PrintPercentage() {
    int64 count = 0;
    for (const int64 bucket : buckets_) {
      count += bucket;
    }
    int index = 0;
    int64 total = 0;
    bool first = true;
    for (int power = -10; power <= 10; power++) {
      if (first) {
        first = false;
      } else {
        printf("\t");
      }
      for (int64 threshold = 1.0 / (1.0 + exp(-power * 0.5)) * count;
           total < threshold; index++) {
        total += buckets_[index];
      }
      printf("%+.7f",
             (static_cast<double>(index) / buckets_.size() * 2 - 1) *
                 kBucketRange);
    }
    printf("\n");
  }

 private:
  vector<double> GetAccumulatedRates(const vector<double> growth_rates) {
    vector<double> growth_accumulated_rates(growth_rates);
    double previous_rate = 0.0;
    for (double& rate : growth_accumulated_rates) {
      rate += previous_rate;
      previous_rate = rate;
    }
    return growth_accumulated_rates;
  }

  void IncrementBucket(double growth_rate) {
    int64 index = round(
        (growth_rate / kBucketRange / 2 + 0.5) * buckets_.size());
    buckets_[max(min(index, static_cast<int64>(buckets_.size()) - 1), 0LL)]++;
  }

  vector<int64> buckets_;
  const int64 trial_;
  const int64 leap_;
};

int main() {
  vector<double> growth_rates;
  for (double old_price = -1.0, price = -1.0;
       scanf("%lf", &price) > 0; old_price = price) {
    if (old_price < 0.0) continue;
    growth_rates.push_back(log(price) - log(old_price));
  }
  Bootstrap bootstrap;
  bootstrap.Calculate(growth_rates);
  bootstrap.PrintPercentage();
  return 0;
}
